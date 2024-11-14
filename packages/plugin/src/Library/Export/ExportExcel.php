<?php

namespace Solspace\Freeform\Library\Export;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    /**
     * @throws Exception
     */
    public function export(): bool|string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($this->getValuesAsArray());

        ob_start();

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        $content = ob_get_contents();

        if (ob_get_length() > 0) {
            ob_end_clean();
        }

        return $content;
    }
}
