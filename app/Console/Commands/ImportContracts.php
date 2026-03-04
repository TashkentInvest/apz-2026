<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportContracts extends Command
{
    protected $signature   = 'apz:import-contracts {--fresh : Truncate table before importing}';
    protected $description = 'Import APZ contracts from grafik_apz.csv into apz_contracts table';

    // grafik_apz.csv comma-separated (105 columns):
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

        // Read header to get monthly date column headers (indexes 24-104)
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            $this->error('Cannot read CSV header.');
            fclose($handle);
            return self::FAILURE;
        }

        // Monthly date headers start at index 24
        $dateHeaders = [];
        for ($i = 24; $i < count($header); $i++) {
            $dateHeaders[$i] = trim($header[$i]);
        }

        $now     = date('Y-m-d H:i:s');
        $batch   = [];
        $count   = 0;
        $skipped = 0;

        $this->info('Importing APZ contracts...');

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) < 20) {
                $skipped++;
                continue;
            }

            $contractId = (int) trim($row[0] ?? 0);
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

            $batch[] = [
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
                'contract_number'     => mb_substr(trim($row[18] ?? ''), 0, 100),
                'contract_date'       => $this->parseDate(trim($row[19] ?? '')),
                'contract_status'     => mb_substr(trim($row[20] ?? ''), 0, 100),
                'contract_value'      => $this->parseAmount($row[21] ?? ''),
                'payment_terms'       => mb_substr(trim($row[22] ?? ''), 0, 50),
                'installments_count'  => (int) trim($row[23] ?? 0),
                'payment_schedule'    => json_encode($schedule),
                'created_at'          => $now,
                'updated_at'          => $now,
            ];

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
        // Remove thousands commas  e.g. 1,234,567.00
        $s = preg_replace('/,(?=\d{3})/', '', $s);
        return (float) $s;
    }
}
