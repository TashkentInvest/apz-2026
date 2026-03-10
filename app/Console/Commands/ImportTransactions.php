<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTransactions extends Command
{
    protected $signature   = 'apz:import-payments {--fresh : Truncate table before importing}';
    protected $description = 'Import APZ payments from fakt_apz.csv into apz_payments table and sync base contracts';

    // fakt_apz.csv supports both:
    // - legacy 24-column structure
    // - compact 13-column structure

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $csvPath = storage_path('dataset-apz/fakt_apz.csv');

        if (!file_exists($csvPath)) {
            $this->error("CSV not found: {$csvPath}");
            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('apz_payments')->truncate();
            $this->info('Table truncated.');
        }

        DB::connection()->disableQueryLog();

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->error('Cannot open CSV file.');
            return self::FAILURE;
        }

        $headerLine = fgets($handle);
        if ($headerLine === false) {
            fclose($handle);
            $this->error('Cannot read CSV header line.');
            return self::FAILURE;
        }

        $delimiter = $this->detectDelimiter($headerLine);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false) {
            fclose($handle);
            $this->error('Cannot read CSV header.');
            return self::FAILURE;
        }

        $headerCount = count($header);
        $isCompactFormat = $headerCount >= 13 && $headerCount < 19;

        $dateIndex = 0;
        $idIndex = 1;
        $innIndex = 2;
        $debitIndex = 3;
        $creditIndex = 4;
        $purposeIndex = 5;
        $flowIndex = $isCompactFormat ? 6 : 12;
        $monthIndex = $isCompactFormat ? 7 : 13;
        $amountIndex = $isCompactFormat ? 8 : 14;
        $districtIndex = $isCompactFormat ? 9 : 15;
        $typeIndex = $isCompactFormat ? 10 : 16;
        $yearIndex = $isCompactFormat ? 11 : 17;
        $companyIndex = $isCompactFormat ? 12 : 18;
        $minColumns = $isCompactFormat ? 13 : 19;

        $now       = date('Y-m-d H:i:s');
        $batch     = [];
        $batchSize = 200;
        $count     = 0;
        $skipped   = 0;

        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% records [%bar%] %elapsed:6s% %memory:6s%');
        $bar->start();

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < $minColumns) {
                $skipped++;
                continue;
            }

            $dateStr = trim($row[$dateIndex] ?? '');
            $parsedDate = $this->parseDate($dateStr);

            // Skip rows that are clearly not data (header repeats, empty/invalid dates)
            if ($parsedDate === null) {
                $skipped++;
                continue;
            }

            $flow = trim($row[$flowIndex] ?? '');
            $type = trim($row[$typeIndex] ?? '');

            // Skip header-repeat rows
            if ($flow === 'Поток' || $type === 'Тип') {
                $skipped++;
                continue;
            }

            $batch[] = [
                'payment_date'    => $parsedDate,
                'contract_id'     => (int) trim($row[$idIndex] ?? 0),
                'inn'             => mb_substr(trim($row[$innIndex] ?? ''), 0, 50),
                'debit_amount'    => $this->parseAmount($row[$debitIndex] ?? '0'),
                'credit_amount'   => $this->parseAmount($row[$creditIndex] ?? '0'),
                'payment_purpose' => mb_substr(trim($row[$purposeIndex] ?? ''), 0, 1000),
                'flow'            => mb_substr($flow, 0, 20),
                'month'           => mb_substr(trim($row[$monthIndex] ?? ''), 0, 50),
                'amount'          => $this->parseAmount($row[$amountIndex] ?? '0'),
                'district'        => mb_substr(trim($row[$districtIndex] ?? ''), 0, 100),
                'type'            => mb_substr($type, 0, 100),
                'year'            => (int) trim($row[$yearIndex] ?? 0),
                'company_name'    => mb_substr(trim($row[$companyIndex] ?? ''), 0, 255),
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            $count++;
            $bar->advance();

            if (count($batch) >= $batchSize) {
                DB::table('apz_payments')->insert($batch);
                $batch = [];
                gc_collect_cycles();
            }
        }

        if (!empty($batch)) {
            DB::table('apz_payments')->insert($batch);
        }

        fclose($handle);
        gc_collect_cycles();

        $createdContracts = $this->syncBaseContractsFromPayments($now);

        $bar->finish();
        $this->newLine();
        $this->info("✓ Imported {$count} APZ payments. Skipped: {$skipped}.");
        $this->info("✓ Created {$createdContracts} base contracts from payments.");

        return self::SUCCESS;
    }

    private function syncBaseContractsFromPayments(string $now): int
    {
        $existingContractIds = DB::table('apz_contracts')
            ->whereNotNull('contract_id')
            ->pluck('contract_id')
            ->map(static fn($id) => (int) $id)
            ->toArray();

        $existingMap = array_fill_keys($existingContractIds, true);

        $contractStats = DB::table('apz_payments as p')
            ->selectRaw("p.contract_id,
                         MAX(NULLIF(TRIM(p.district), '')) as district,
                         MAX(NULLIF(TRIM(p.inn), '')) as inn,
                         MAX(NULLIF(TRIM(p.company_name), '')) as company_name,
                         MIN(p.payment_date) as first_payment_date")
            ->whereNotNull('p.contract_id')
            ->where('p.contract_id', '>', 0)
            ->groupBy('p.contract_id')
            ->get();

        $batch = [];
        $created = 0;

        foreach ($contractStats as $row) {
            $contractId = (int) ($row->contract_id ?? 0);
            if ($contractId <= 0 || isset($existingMap[$contractId])) {
                continue;
            }

            $investorName = trim((string) ($row->company_name ?? ''));
            if ($investorName === '') {
                $investorName = '—';
            }

            $batch[] = [
                'contract_id' => $contractId,
                'district' => trim((string) ($row->district ?? '')) ?: null,
                'investor_name' => mb_substr($investorName, 0, 255),
                'inn' => mb_substr(trim((string) ($row->inn ?? '')), 0, 50),
                'contract_number' => (string) $contractId,
                'contract_date' => $row->first_payment_date ?: null,
                'contract_status' => 'Амалдаги',
                'contract_value' => 0,
                'payment_terms' => null,
                'installments_count' => null,
                // Schedule is managed in system UI, not imported from grafik file.
                'payment_schedule' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $existingMap[$contractId] = true;
            $created++;

            if (count($batch) >= 300) {
                DB::table('apz_contracts')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('apz_contracts')->insert($batch);
        }

        return $created;
    }

    private function detectDelimiter(string $headerLine): string
    {
        $commaCount = substr_count($headerLine, ',');
        $semicolonCount = substr_count($headerLine, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function parseDate(string $s): ?string
    {
        $s = trim($s);
        if (empty($s)) return null;
        // YYYY-MM-DD
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s)) {
            return $s;
        }
        // DD.MM.YYYY
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $s, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        $t = strtotime(str_replace('.', '-', $s));
        return $t !== false ? date('Y-m-d', $t) : null;
    }

    private function parseAmount(string $s): float
    {
        // Remove figure spaces, NBSP, regular spaces, dashes
        $s = trim($s);
        if ($s === '-' || $s === ' - ' || preg_match('/^[\s\-\x{2007}]+$/u', $s)) {
            return 0.0;
        }
        // Remove all spaces and non-breaking spaces
        $s = preg_replace('/[\s\x{00A0}\x{2007}]/u', '', $s);
        // Remove thousand separators (commas before 3 digits pattern)
        $s = preg_replace('/,(?=\d{3})/', '', $s);

        // Handle decimal comma (e.g. 500000000,00)
        if (str_contains($s, ',') && !str_contains($s, '.')) {
            $s = str_replace(',', '.', $s);
        }

        return (float) $s;
    }
}
