<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Json;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;

class ExportJson extends AbstractSubmissionExport
{
    public static function getLabel(): string
    {
        return 'JSON';
    }

    public function getMimeType(): string
    {
        return 'application/octet-stream';
    }

    public function getFileExtension(): string
    {
        return 'json';
    }

    public function export($resource): void
    {
        // TODO: implement
        $output = [];
        foreach ($this->getRowBatch() as $row) {
            $rowData = [];
            foreach ($row as $key => $column) {
                $rowData[$key] = $column;
            }

            $output[] = $rowData;
        }
    }
}
