<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Excel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;

class ExportExcel extends ExportCsv
{
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($this->getValuesAsArray());

        ob_start();

        $writer = new Xlsx($spreadsheet);
        $writer->save($resource);
    }
}
