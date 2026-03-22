<?php

namespace App\Services\MasterCatalog;

use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MasterCatalogTemplateGenerator
{
    public function __construct(
        private readonly MasterCatalogWorkbookSchema $schema,
    ) {
    }

    public function generate(string $absolutePath): string
    {
        $spreadsheet = new Spreadsheet();
        $sheets = $this->schema->sheets();

        foreach ($sheets as $index => $sheetMeta) {
            $worksheet = $index === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();

            $worksheet->setTitle($sheetMeta['name']);

            $columns = $sheetMeta['columns'];
            foreach ($columns as $columnIndex => $columnName) {
                $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . '1';
                $worksheet->setCellValue($cell, $columnName);
            }

            $headerRange = 'A1:' . Coordinate::stringFromColumnIndex(count($columns)) . '1';
            $worksheet->getStyle($headerRange)->getFont()->setBold(true);
            $worksheet->getStyle($headerRange)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()
                ->setRGB('E5E7EB');
            $worksheet->freezePane('A2');

            $rowIndex = 2;
            foreach ($sheetMeta['examples'] as $exampleRow) {
                foreach ($columns as $columnIndex => $columnName) {
                    $cell = Coordinate::stringFromColumnIndex($columnIndex + 1) . (string) $rowIndex;
                    $worksheet->setCellValue($cell, $exampleRow[$columnName] ?? '');
                }

                $rowIndex++;
            }

            $worksheet->setAutoFilter($headerRange);
            foreach (range(1, count($columns)) as $columnIndex) {
                $worksheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
            }

            if ($sheetMeta['name'] === 'readme') {
                $worksheet->getStyle('A:B')->getAlignment()->setWrapText(true);
                $worksheet->getColumnDimension('A')->setWidth(28);
                $worksheet->getColumnDimension('B')->setWidth(120);
            }
        }

        $spreadsheet->setActiveSheetIndex(0);
        File::ensureDirectoryExists(dirname($absolutePath));

        $writer = new Xlsx($spreadsheet);
        $writer->save($absolutePath);

        return $absolutePath;
    }
}
