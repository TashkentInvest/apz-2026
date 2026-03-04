<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTransactions extends Command
{
    protected $signature   = 'apz:import-payments {--fresh : Truncate table before importing}';
    protected $description = 'Import APZ payments from fakt-apz.csv into apz_payments table';

    // fakt-apz.csv comma-separated columns (24 total):
    // [0]  date        [1]  ID          [2]  INN
    // [3]  debit       [4]  credit      [5]  payment_purpose
    // [6]  date(dup)   [7]  ID(dup)     [8]  INN(dup)
    // [9]  debit(dup)  [10] credit(dup) [11] payment_purpose(dup)
    // [12] flow        [13] month       [14] amount
    // [15] district    [16] type        [17] year
    // [18] company_name (and repeated columns 19-23)

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $csvPath = storage_path('dataset-apz/fakt-apz.csv');

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

        // Skip header row
        fgetcsv($handle, 0, ',');

        $now       = date('Y-m-d H:i:s');
        $batch     = [];
        $batchSize = 200;
        $count     = 0;
        $skipped   = 0;

        $bar = $this->output->createProgressBar();
        $bar->setFormat(' %current% records [%bar%] %elapsed:6s% %memory:6s%');
        $bar->start();

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            if (count($row) < 17) {
                $skipped++;
                continue;
            }

            $dateStr = trim($row[0] ?? '');
            // Skip rows that are clearly not data (header repeats, empty first col)
            if (!preg_match('/^20\d{2}/', $dateStr)) {
                $skipped++;
                continue;
            }

            $flow = trim($row[12] ?? '');
            $type = trim($row[16] ?? '');

            // Skip header-repeat rows
            if ($flow === 'Поток' || $type === 'Тип') {
                $skipped++;
                continue;
            }

            $batch[] = [
                'payment_date'    => $this->parseDate($dateStr),
                'contract_id'     => (int) trim($row[1] ?? 0),
                'inn'             => mb_substr(trim($row[2] ?? ''), 0, 50),
                'debit_amount'    => $this->parseAmount($row[3] ?? '0'),
                'credit_amount'   => $this->parseAmount($row[4] ?? '0'),
                'payment_purpose' => mb_substr(trim($row[5] ?? ''), 0, 1000),
                'flow'            => mb_substr($flow, 0, 20),
                'month'           => mb_substr(trim($row[13] ?? ''), 0, 50),
                'amount'          => $this->parseAmount($row[14] ?? '0'),
                'district'        => mb_substr(trim($row[15] ?? ''), 0, 100),
                'type'            => mb_substr($type, 0, 100),
                'year'            => (int) trim($row[17] ?? 0),
                'company_name'    => mb_substr(trim($row[18] ?? ''), 0, 255),
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

        $bar->finish();
        $this->newLine();
        $this->info("✓ Imported {$count} APZ payments. Skipped: {$skipped}.");

        return self::SUCCESS;
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
        return (float) $s;
    }
}
