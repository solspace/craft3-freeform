<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;

class ExportExcel extends ExportCsv
{
    private Worksheet $sheet;
    private int $row = 1;

    public static function getLabel(): string
    {
        return 'Excel';
    }

    public function getMimeType(): string
    {
        return 'application/vnd.ms-excel';
    }

    public function getFileExtension(): string
    {
        return 'xlsx';
    }

    public function export($resource): void
    {
        $spreadsheet = new Spreadsheet();
        $this->sheet = $spreadsheet->getActiveSheet();

        parent::export($resource);

        $writer = new Xlsx($spreadsheet);
        $writer->save($resource);
    }

    protected function writeToFile($resource, array $values): void
    {
        $this->sheet->fromArray(
            $values,
            startCell: 'A'.$this->row++,
        );
    }
}
