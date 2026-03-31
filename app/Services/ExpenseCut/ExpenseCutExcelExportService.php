<?php

namespace App\Services\ExpenseCut;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

class ExpenseCutExcelExportService
{
    public function createFile(array $columns, array $rows, CarbonInterface $inicio, CarbonInterface $fin): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('ZipArchive no esta disponible para generar el archivo Excel.');
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir) && !mkdir($tmpDir, 0775, true) && !is_dir($tmpDir)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal para exportaciones.');
        }

        $filename = sprintf(
            'corte_gastos_%s_%s.xlsx',
            $inicio->format('Y-m-d'),
            $fin->format('Y-m-d')
        );

        $path = $tmpDir . DIRECTORY_SEPARATOR . 'expense_cut_' . Str::uuid()->toString() . '.xlsx';

        $allRows = [array_map(fn ($column) => (string) $column, $columns), ...$rows];
        [$sheetXml, $sharedStringsXml] = $this->buildWorksheetAndSharedStrings($allRows);

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelationsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/sharedStrings.xml', $sharedStringsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        return [
            'path' => $path,
            'filename' => $filename,
        ];
    }

    private function buildWorksheetAndSharedStrings(array $rows): array
    {
        $sharedMap = [];
        $sharedValues = [];
        $sharedTotalCount = 0;
        $sheetRows = [];
        $maxColumns = 0;

        foreach ($rows as $rowIndex => $rowValues) {
            $cells = [];
            $excelRow = $rowIndex + 1;
            $maxColumns = max($maxColumns, count($rowValues));

            foreach ($rowValues as $columnIndex => $rawValue) {
                $value = (string) ($rawValue ?? '');
                if (!array_key_exists($value, $sharedMap)) {
                    $sharedMap[$value] = count($sharedValues);
                    $sharedValues[] = $value;
                }

                $sharedTotalCount++;
                $sharedIndex = $sharedMap[$value];
                $cellRef = $this->columnName($columnIndex + 1) . $excelRow;
                $cells[] = '<c r="' . $cellRef . '" t="s"><v>' . $sharedIndex . '</v></c>';
            }

            $sheetRows[] = '<row r="' . $excelRow . '">' . implode('', $cells) . '</row>';
        }

        $dimension = 'A1';
        if ($maxColumns > 0 && count($rows) > 0) {
            $dimension = 'A1:' . $this->columnName($maxColumns) . count($rows);
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<dimension ref="' . $dimension . '"/>'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '</worksheet>';

        $sharedItems = array_map(function (string $value) {
            return '<si><t xml:space="preserve">' . $this->xmlEscape($value) . '</t></si>';
        }, $sharedValues);

        $sharedXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . $sharedTotalCount . '" uniqueCount="' . count($sharedValues) . '">'
            . implode('', $sharedItems)
            . '</sst>';

        return [$sheetXml, $sharedXml];
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '</Types>';
    }

    private function rootRelationsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Gastos" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelationsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function columnName(int $index): string
    {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
