<?php

namespace App\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SimpleXlsxExportService
{
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
