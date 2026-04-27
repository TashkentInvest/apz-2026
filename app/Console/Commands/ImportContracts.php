<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportContracts extends Command
{
    protected $signature   = 'apz:import-contracts {--fresh : Truncate table before importing}';
    protected $description = 'Import APZ contracts from grafik_apz.csv into apz_contracts table';

    // grafik_apz.csv delimiter-separated (105 columns):
    // [0]  ID              [1]  district        [2]  mfy
    // [3]  address         [4]  build_volume    [5]  coefficient
    // [6]  zone            [7]  permit          [8]  apz_number
    // [9]  council_dec     [10] expertise       [11] construction_issues
    // [12] object_type     [13] client_type     [14] investor_name
    // [15] INN             [16] phone           [17] investor_address
    // [18] contract_num    [19] contract_date   [20] contract_status
    // [21] contract_value  [22] payment_terms   [23] installments_count
    // [24..104] monthly payment dates (4/30/2024 .. 12/31/2030)

    public function handle(): int
    {
        ini_set('memory_limit', '256M');

        $csvPath = storage_path('dataset-apz/grafik_apz.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV not found: {$csvPath}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('apz_contracts')->truncate();
            $this->info('Table truncated.');
        }

        DB::connection()->disableQueryLog();

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->error('Cannot open CSV file.');
            return self::FAILURE;
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            $this->error('Cannot read CSV file.');
            fclose($handle);
            return self::FAILURE;
        }

        $delimiter = $this->detectCsvDelimiter($firstLine);
        rewind($handle);

        // Read header to get monthly date column headers (indexes 24-104)
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            $this->error('Cannot read CSV header.');
            fclose($handle);
            return self::FAILURE;
        }

        $idxContractId = $this->findColumnIndex($header, ['id'], 0);
        $idxContractValue = $this->findColumnIndex($header, ['shartnoma qiymati', 'contract value'], 21);
        $idxContractDate = $this->findColumnIndex($header, ['shartnoma sana', 'contract date'], 19);
        $idxContractNumber = $this->findColumnIndex($header, ['shartnoma nomer', 'contract number'], 18);
        $idxPaymentTerms = $this->findColumnIndex($header, ["to'lov shart", 'payment terms'], 22);
        $idxInstallments = $this->findColumnIndex($header, ['reja-jadval', 'installments'], 23);
        $idxDemandNumber = $this->findColumnIndex($header, ['talabnoma nomer', 'talabnoma', 'demand number'], 24);
        $idxDemandDate = $this->findColumnIndex($header, ['talabnoma sana', 'demand date'], 25);

        // Only real schedule date columns (e.g. 30.04.24, 31.05.24, ...)
        $dateHeaders = $this->extractScheduleDateColumns($header);

        $now     = date('Y-m-d H:i:s');
        $batch   = [];
        $count   = 0;
        $skipped = 0;
        $useFresh = (bool) $this->option('fresh');
        $existingContractMap = [];

        if (!$useFresh) {
            $existingContractMap = DB::table('apz_contracts')
                ->whereNotNull('contract_id')
                ->pluck('contract_id')
                ->map(static fn ($id) => (int) $id)
                ->flip()
                ->toArray();
        }

        $this->info('Importing APZ contracts...');

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < 20) {
                $skipped++;
                continue;
            }

            $contractId = (int) trim($row[$idxContractId] ?? 0);
            if ($contractId === 0) {
                $skipped++;
                continue;
            }

            // Build payment schedule JSON: { "M/D/YYYY": amount, ... }
            $schedule = [];
            foreach ($dateHeaders as $idx => $dateHeader) {
                if (!isset($row[$idx])) continue;
                $val = $this->parseAmount($row[$idx]);
                if ($val > 0) {
                    $schedule[$dateHeader] = $val;
                }
            }

            $record = [
                'contract_id'         => $contractId,
                'district'            => mb_substr(trim($row[1] ?? ''), 0, 100),
                'mfy'                 => mb_substr(trim($row[2] ?? ''), 0, 255),
                'address'             => mb_substr(trim($row[3] ?? ''), 0, 1000),
                'build_volume'        => $this->parseAmount($row[4] ?? ''),
                'coefficient'         => mb_substr(trim($row[5] ?? ''), 0, 50),
                'zone'                => mb_substr(trim($row[6] ?? ''), 0, 50),
                'permit'              => mb_substr(trim($row[7] ?? ''), 0, 100),
                'apz_number'          => mb_substr(trim($row[8] ?? ''), 0, 100),
                'council_decision'    => mb_substr(trim($row[9] ?? ''), 0, 255),
                'expertise'           => mb_substr(trim($row[10] ?? ''), 0, 255),
                'construction_issues' => mb_substr(trim($row[11] ?? ''), 0, 255),
                'object_type'         => mb_substr(trim($row[12] ?? ''), 0, 255),
                'client_type'         => mb_substr(trim($row[13] ?? ''), 0, 255),
                'investor_name'       => mb_substr(trim($row[14] ?? ''), 0, 255),
                'inn'                 => mb_substr(trim($row[15] ?? ''), 0, 50),
                'phone'               => mb_substr(trim($row[16] ?? ''), 0, 50),
                'investor_address'    => mb_substr(trim($row[17] ?? ''), 0, 1000),
                'contract_number'     => mb_substr(trim($row[$idxContractNumber] ?? ''), 0, 100),
                'contract_date'       => $this->parseDate(trim($row[$idxContractDate] ?? '')),
                'contract_status'     => mb_substr(trim($row[20] ?? ''), 0, 100),
                'contract_value'      => $this->parseAmount($row[$idxContractValue] ?? ''),
                'payment_terms'       => $this->normalizePaymentTerms($row[$idxPaymentTerms] ?? ''),
                'installments_count'  => (int) trim($row[$idxInstallments] ?? 0),
                'demand_letter_number' => mb_substr(trim($row[$idxDemandNumber] ?? ''), 0, 100),
                'demand_letter_date'  => $this->parseDate(trim($row[$idxDemandDate] ?? '')),
                'payment_schedule'    => json_encode($schedule),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

            if ($useFresh || !isset($existingContractMap[$contractId])) {
                $batch[] = $record;
                $existingContractMap[$contractId] = true;
            } else {
                $updatePayload = $record;
                unset($updatePayload['created_at']);

                DB::table('apz_contracts')
                    ->where('contract_id', $contractId)
                    ->update($updatePayload);
            }

            $count++;

            if (count($batch) >= 100) {
                DB::table('apz_contracts')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('apz_contracts')->insert($batch);
        }

        fclose($handle);

        $this->info("✓ Imported {$count} APZ contracts. Skipped: {$skipped}.");
        return self::SUCCESS;
    }

    private function parseDate(string $s): ?string
    {
        $s = trim($s);
        if (empty($s)) return null;

        // D.M.YYYY
        if (preg_match('#^(\d{1,2})\.(\d{1,2})\.(\d{4})$#', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // M/D/YYYY
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $s, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[1], $m[2]);
        }
        // YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s)) return $s;
        $t = strtotime($s);
        return $t !== false ? date('Y-m-d', $t) : null;
    }

    private function parseAmount(string $s): float
    {
        $s = trim($s);
        if ($s === '' || $s === '-' || $s === ' - ') return 0.0;

        // Remove spaces, NBSP, figure spaces
        $s = preg_replace('/[\s\x{00A0}\x{2007}]/u', '', $s);

        if (str_contains($s, ',') && str_contains($s, '.')) {
            $lastComma = strrpos($s, ',');
            $lastDot = strrpos($s, '.');

            if ($lastComma !== false && $lastDot !== false && $lastComma > $lastDot) {
                $s = str_replace('.', '', $s);
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        } elseif (str_contains($s, ',')) {
            if (preg_match('/,\d{1,4}$/', $s)) {
                $s = str_replace(',', '.', $s);
            } else {
                $s = str_replace(',', '', $s);
            }
        }

        return (float) $s;
    }

    private function detectCsvDelimiter(string $line): string
    {
        $line = trim($line);
        if ($line === '') {
            return ',';
        }

        $commaCount = substr_count($line, ',');
        $semicolonCount = substr_count($line, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function normalizePaymentTerms($value): string
    {
        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-' || $raw === ' - ') {
            return '';
        }

        $raw = preg_replace('/\s+/u', '', $raw);
        $raw = str_replace('%', '', $raw);

        if (preg_match('/^([0-9]+(?:[\.,][0-9]+)?)\/([0-9]+(?:[\.,][0-9]+)?)$/u', $raw, $m)) {
            $first = str_replace(',', '.', $m[1]);
            $second = str_replace(',', '.', $m[2]);
            return $first . '/' . $second;
        }

        if (preg_match('/^[0-9]+(?:[\.,][0-9]+)?$/u', $raw)) {
            return str_replace(',', '.', $raw);
        }

        return mb_substr($raw, 0, 50);
    }

    private function findColumnIndex(array $header, array $aliases, int $fallback): int
    {
        foreach ($header as $index => $name) {
            $normalized = mb_strtolower(trim((string) $name));
            if ($normalized === '') {
                continue;
            }

            foreach ($aliases as $alias) {
                if (str_contains($normalized, mb_strtolower($alias))) {
                    return (int) $index;
                }
            }
        }

        return $fallback;
    }

    private function extractScheduleDateColumns(array $header): array
    {
        $result = [];

        foreach ($header as $index => $name) {
            $raw = trim((string) $name);
            if ($raw === '') {
                continue;
            }

            if (!preg_match('/^\d{1,2}\.\d{1,2}\.\d{2,4}$/', $raw)) {
                continue;
            }

            $normalized = $this->normalizeScheduleHeaderDate($raw);
            if ($normalized === null) {
                continue;
            }

            $result[$index] = $normalized;
        }

        return $result;
    }

    private function normalizeScheduleHeaderDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{2})$/', $value, $m)) {
            $day = (int) $m[1];
            $month = (int) $m[2];
            $year = 2000 + (int) $m[3];

            if (checkdate($month, $day, $year)) {
                return sprintf('%02d.%02d.%04d', $day, $month, $year);
            }
        }

        foreach (['d.m.Y', 'j.n.Y', 'd.m.y', 'j.n.y'] as $format) {
            try {
                $date = \Carbon\CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    if ((int) $date->format('Y') < 1900 && preg_match('/\.\d{2}$/', $value)) {
                        $date = $date->addYears(2000);
                    }
                    return $date->format('d.m.Y');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }
}
