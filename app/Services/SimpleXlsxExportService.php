<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SimpleXlsxExportService
{
    public function exportApzDatasetsToStorage(array $filters = [], string $relativePath = 'dataset-apz'): array
    {
        $normalizedFilters = [
            'district' => $this->normalizeFilterValue($filters['district'] ?? null),
            'month' => $this->normalizeFilterValue($filters['month'] ?? null),
            'year' => $this->normalizeYearFilterValue($filters['year'] ?? null),
        ];

        $outputDir = $this->resolveStorageDirectory($relativePath);
        if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
            throw new \RuntimeException('Cannot create export directory: ' . $outputDir);
        }

        $faktPath = $outputDir . DIRECTORY_SEPARATOR . 'fakt_apz.csv';
        $grafikPath = $outputDir . DIRECTORY_SEPARATOR . 'grafik_apz.csv';

        $paymentsCount = $this->writePaymentsCsv($faktPath, $normalizedFilters);
        $contractsCount = $this->writeContractsCsv($grafikPath, $normalizedFilters);

        return [
            'directory' => $outputDir,
            'fakt_path' => $faktPath,
            'grafik_path' => $grafikPath,
            'fakt_rows' => $paymentsCount,
            'grafik_rows' => $contractsCount,
        ];
    }

    public function download(string $fileName, array $sheets): BinaryFileResponse
    {
        if (!class_exists(\ZipArchive::class)) {
            abort(500, 'ZIP extension is required for XLSX export.');
        }

        $preparedSheets = $this->prepareSheets($sheets);
        if (empty($preparedSheets)) {
            $preparedSheets = [
                ['name' => 'Sheet1', 'rows' => [['No data']]],
            ];
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0775, true);
        }

        $tmpPath = $tmpDir . DIRECTORY_SEPARATOR . Str::uuid()->toString() . '.xlsx';

        $zip = new \ZipArchive();
        if ($zip->open($tmpPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Unable to create XLSX file.');
        }

        $sheetCount = count($preparedSheets);

        $zip->addFromString('[Content_Types].xml', $this->buildContentTypesXml($sheetCount));
        $zip->addFromString('_rels/.rels', $this->buildRootRelationshipsXml());
        $zip->addFromString('xl/workbook.xml', $this->buildWorkbookXml($preparedSheets));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->buildWorkbookRelationshipsXml($sheetCount));

        foreach ($preparedSheets as $index => $sheet) {
            $sheetNumber = $index + 1;
            $zip->addFromString('xl/worksheets/sheet' . $sheetNumber . '.xml', $this->buildWorksheetXml($sheet['rows']));
        }

        $zip->close();

        return response()->download(
            $tmpPath,
            $fileName,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        )->deleteFileAfterSend(true);
    }

    private function prepareSheets(array $sheets): array
    {
        $prepared = [];
        $usedNames = [];

        foreach ($sheets as $index => $sheet) {
            $rawName = is_array($sheet) && isset($sheet['name']) ? (string) $sheet['name'] : ('Sheet' . ($index + 1));
            $rows = is_array($sheet) && isset($sheet['rows']) && is_array($sheet['rows']) ? $sheet['rows'] : [];

            $safeName = $this->sanitizeSheetName($rawName);
            $safeName = $this->makeUniqueSheetName($safeName, $usedNames);
            $usedNames[] = $safeName;

            $prepared[] = [
                'name' => $safeName,
                'rows' => $this->normalizeRows($rows),
            ];
        }

        return $prepared;
    }

    private function writePaymentsCsv(string $path, array $filters): int
    {
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open file for writing: ' . $path);
        }

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, [
            'Дата',
            'ID',
            'ИНН',
            ' Сумма дебет ',
            ' Сумма кредит ',
            'Назначение платежа',
            'Поток',
            'Месяц',
            ' Cумма ',
            'Район',
            'Тип',
            'ГОД',
            'Корхона номи',
        ]);

        $rowsCount = 0;

        $query = DB::table('apz_payments')
            ->select([
                'id',
                'payment_date',
                'contract_id',
                'inn',
                'debit_amount',
                'credit_amount',
                'payment_purpose',
                'flow',
                'month',
                'amount',
                'district',
                'type',
                'year',
                'company_name',
            ])
            ->orderBy('id');

        $this->applyPaymentFilters($query, $filters);

        $query->chunkById(2000, function ($rows) use (&$rowsCount, $handle) {
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $this->formatDatasetDate($row->payment_date),
                    (int) ($row->contract_id ?? 0),
                    (string) ($row->inn ?? ''),
                    $this->formatDatasetAmount($row->debit_amount),
                    $this->formatDatasetAmount($row->credit_amount),
                    (string) ($row->payment_purpose ?? ''),
                    (string) ($row->flow ?? ''),
                    (string) ($row->month ?? ''),
                    $this->formatDatasetAmount($row->amount),
                    (string) ($row->district ?? ''),
                    (string) ($row->type ?? ''),
                    (string) ($row->year ?? ''),
                    (string) ($row->company_name ?? ''),
                ]);

                $rowsCount++;
            }
        }, 'id');

        fclose($handle);

        return $rowsCount;
    }

    private function writeContractsCsv(string $path, array $filters): int
    {
        $dateHeaders = $this->buildScheduleHeaders();

        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Cannot open file for writing: ' . $path);
        }

        fwrite($handle, "\xEF\xBB\xBF");

        fputcsv($handle, array_merge([
            'ID',
            'Туман',
            'МФЙ',
            'манзил тўлиқ',
            'Қурилиш ҳажми (метр.куп)',
            'Коэффицент',
            'Зона',
            'Рухсатнома',
            'АПЗ номер',
            'Кенгаш хулосаси',
            'Йиғма экспертиза хулосаси',
            'қурилишда муамолари ҳолати',
            'Объект тури',
            'БУЮРТМАЧИ ТУРИ',
            'Инвестор номи',
            'ИНН/ПИНФЛ',
            'телефон номер',
            'инвестор манзили',
            'шартнома номер',
            'шартнома санаси',
            'Шартнома ҳолати',
            'Шартнома қиймати',
            'Тўлов шарти',
            'reja-jadval',
        ], $dateHeaders));

        $rowsCount = 0;

        $query = DB::table('apz_contracts')
            ->select([
                'id',
                'contract_id',
                'district',
                'mfy',
                'address',
                'build_volume',
                'coefficient',
                'zone',
                'permit',
                'apz_number',
                'council_decision',
                'expertise',
                'construction_issues',
                'object_type',
                'client_type',
                'investor_name',
                'inn',
                'phone',
                'investor_address',
                'contract_number',
                'contract_date',
                'contract_status',
                'contract_value',
                'payment_terms',
                'installments_count',
                'payment_schedule',
            ])
            ->orderBy('id');

        if ($filters['district'] !== null || $filters['month'] !== null || $filters['year'] !== null) {
            $query->whereIn('contract_id', function ($subQuery) use ($filters) {
                $subQuery->from('apz_payments')
                    ->selectRaw('DISTINCT contract_id')
                    ->whereNotNull('contract_id');

                $this->applyPaymentFilters($subQuery, $filters);
            });
        }

        $query->chunkById(1000, function ($rows) use (&$rowsCount, $handle, $dateHeaders) {
            foreach ($rows as $row) {
                $scheduleMap = $this->normalizeScheduleMap($row->payment_schedule);
                $scheduleCells = [];

                foreach ($dateHeaders as $header) {
                    $scheduleCells[] = $scheduleMap[$header] ?? '';
                }

                fputcsv($handle, array_merge([
                    (int) ($row->contract_id ?? 0),
                    (string) ($row->district ?? ''),
                    (string) ($row->mfy ?? ''),
                    (string) ($row->address ?? ''),
                    $this->formatDatasetAmount($row->build_volume),
                    (string) ($row->coefficient ?? ''),
                    (string) ($row->zone ?? ''),
                    (string) ($row->permit ?? ''),
                    (string) ($row->apz_number ?? ''),
                    (string) ($row->council_decision ?? ''),
                    (string) ($row->expertise ?? ''),
                    (string) ($row->construction_issues ?? ''),
                    (string) ($row->object_type ?? ''),
                    (string) ($row->client_type ?? ''),
                    (string) ($row->investor_name ?? ''),
                    (string) ($row->inn ?? ''),
                    (string) ($row->phone ?? ''),
                    (string) ($row->investor_address ?? ''),
                    (string) ($row->contract_number ?? ''),
                    $this->formatDatasetDate($row->contract_date),
                    (string) ($row->contract_status ?? ''),
                    $this->formatDatasetAmount($row->contract_value),
                    (string) ($row->payment_terms ?? ''),
                    (string) ($row->installments_count ?? ''),
                ], $scheduleCells));

                $rowsCount++;
            }
        }, 'id');

        fclose($handle);

        return $rowsCount;
    }

    private function applyPaymentFilters($query, array $filters): void
    {
        if (($filters['district'] ?? null) !== null) {
            $query->where('district', $filters['district']);
        }

        if (($filters['month'] ?? null) !== null) {
            $query->where('month', $filters['month']);
        }

        if (($filters['year'] ?? null) !== null) {
            $query->where('year', $filters['year']);
        }
    }

    private function normalizeScheduleMap($rawSchedule): array
    {
        $decoded = [];

        if (is_string($rawSchedule) && trim($rawSchedule) !== '') {
            $parsed = json_decode($rawSchedule, true);
            if (is_array($parsed)) {
                $decoded = $parsed;
            }
        }

        if (is_array($rawSchedule)) {
            $decoded = $rawSchedule;
        }

        $normalized = [];

        foreach ($decoded as $dateKey => $amount) {
            $normalizedDate = $this->normalizeDateKey((string) $dateKey);
            if ($normalizedDate === null) {
                continue;
            }

            $amountValue = $this->formatDatasetAmount($amount);
            if ($amountValue === '') {
                continue;
            }

            $normalized[$normalizedDate] = $amountValue;
        }

        return $normalized;
    }

    private function normalizeDateKey(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $formats = ['d.m.Y', 'j.n.Y', 'Y-m-d', 'n/j/Y', 'm/d/Y'];
        foreach ($formats as $format) {
            try {
                $date = CarbonImmutable::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date->format('d.m.Y');
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        try {
            return CarbonImmutable::parse($value)->format('d.m.Y');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function buildScheduleHeaders(): array
    {
        $headers = [];
        $cursor = CarbonImmutable::create(2024, 4, 1)->startOfMonth();
        $end = CarbonImmutable::create(2030, 12, 1)->startOfMonth();

        while ($cursor->lessThanOrEqualTo($end)) {
            $headers[] = $cursor->endOfMonth()->format('d.m.Y');
            $cursor = $cursor->addMonth()->startOfMonth();
        }

        return $headers;
    }

    private function formatDatasetDate($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        try {
            return CarbonImmutable::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatDatasetAmount($value): string
    {
        if ($value === null) {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '' || $raw === '-') {
            return '';
        }

        $clean = preg_replace('/[\s\x{00A0}\x{2007}]/u', '', $raw);
        $clean = preg_replace('/,(?=\d{3})/', '', $clean);

        if (!is_numeric($clean)) {
            return $raw;
        }

        $number = (float) $clean;
        if ((int) $number === $number) {
            return (string) ((int) $number);
        }

        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    private function normalizeFilterValue($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        return $value === '' ? null : $value;
    }

    private function normalizeYearFilterValue($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $year = (int) $value;
        return $year > 0 ? $year : null;
    }

    private function resolveStorageDirectory(string $optionPath): string
    {
        $relative = trim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $optionPath));
        if ($relative === '') {
            $relative = 'dataset-apz';
        }

        return storage_path($relative);
    }

    private function normalizeRows(array $rows): array
    {
        $normalized = [];
        foreach ($rows as $row) {
            if (is_array($row)) {
                $normalized[] = array_values($row);
            } else {
                $normalized[] = [(string) $row];
            }
        }

        return $normalized;
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = preg_replace('/[\\\\\/:*?\[\]]/', '_', trim($name));
        if ($name === null || $name === '') {
            $name = 'Sheet';
        }

        if (mb_strlen($name) > 31) {
            $name = mb_substr($name, 0, 31);
        }

        return $name;
    }

    private function makeUniqueSheetName(string $name, array $usedNames): string
    {
        if (!in_array($name, $usedNames, true)) {
            return $name;
        }

        $base = $name;
        $counter = 2;
        while (true) {
            $suffix = '_' . $counter;
            $maxLen = 31 - mb_strlen($suffix);
            $candidate = mb_substr($base, 0, max($maxLen, 1)) . $suffix;

            if (!in_array($candidate, $usedNames, true)) {
                return $candidate;
            }

            $counter++;
        }
    }

    private function buildContentTypesXml(int $sheetCount): string
    {
        $overrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . $overrides
            . '</Types>';
    }

    private function buildRootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function buildWorkbookXml(array $sheets): string
    {
        $sheetXml = '';
        foreach ($sheets as $index => $sheet) {
            $sheetId = $index + 1;
            $sheetXml .= '<sheet name="' . $this->xmlEscape($sheet['name']) . '" sheetId="' . $sheetId . '" r:id="rId' . $sheetId . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheetXml . '</sheets>'
            . '</workbook>';
    }

    private function buildWorkbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $relationships
            . '</Relationships>';
    }

    private function buildWorksheetXml(array $rows): string
    {
        if (empty($rows)) {
            $rows = [['No data']];
        }

        $sheetData = '';
        foreach ($rows as $rowIndex => $row) {
            $excelRow = $rowIndex + 1;
            $cells = '';

            foreach ($row as $columnIndex => $value) {
                $columnLetter = $this->columnToLetter($columnIndex + 1);
                $cellRef = $columnLetter . $excelRow;
                $cells .= $this->buildCellXml($cellRef, $value);
            }

            $sheetData .= '<row r="' . $excelRow . '">' . $cells . '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '</worksheet>';
    }

    private function buildCellXml(string $cellRef, $value): string
    {
        if ($this->isNumericCell($value)) {
            $number = is_bool($value) ? ($value ? 1 : 0) : (string) $value;
            return '<c r="' . $cellRef . '"><v>' . $this->xmlEscape($number) . '</v></c>';
        }

        $text = $this->xmlEscape((string) ($value ?? ''));
        return '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">' . $text . '</t></is></c>';
    }

    private function isNumericCell($value): bool
    {
        if (is_int($value) || is_float($value) || is_bool($value)) {
            return true;
        }

        if (!is_string($value)) {
            return false;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        return preg_match('/^-?\d+(\.\d+)?$/', $trimmed) === 1;
    }

    private function columnToLetter(int $column): string
    {
        $letters = '';
        while ($column > 0) {
            $mod = ($column - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $column = (int) (($column - $mod - 1) / 26);
        }

        return $letters;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
