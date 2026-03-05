<?php

namespace App\Console\Commands;

use App\Services\SimpleXlsxExportService;
use Illuminate\Console\Command;

class ExportApzDatasets extends Command
{
    protected $signature = 'apz:export-datasets
        {--district= : Filter by district}
        {--month= : Filter by month}
        {--year= : Filter by year}
        {--path=dataset-apz : Directory inside storage/ to write CSV files}';

    protected $description = 'Export APZ data into fakt_apz.csv and grafik_apz.csv';

    public function handle(): int
    {
        $this->info('Exporting dataset files from seeded DB ...');

        $result = app(SimpleXlsxExportService::class)->exportApzDatasetsToStorage([
            'district' => $this->option('district'),
            'month' => $this->option('month'),
            'year' => $this->option('year'),
        ], (string) ($this->option('path') ?: 'dataset-apz'));

        $this->newLine();
        $this->info('✓ Export completed');
        $this->line('fakt_apz.csv rows: ' . (int) ($result['fakt_rows'] ?? 0));
        $this->line('grafik_apz.csv rows: ' . (int) ($result['grafik_rows'] ?? 0));
        $this->line('Directory: ' . (string) ($result['directory'] ?? ''));

        return self::SUCCESS;
    }
}
