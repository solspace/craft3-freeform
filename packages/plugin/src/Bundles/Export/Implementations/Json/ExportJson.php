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
        fwrite($resource, "[\n");

        $total = $this->getQuery()->count();
        $count = 0;

        foreach ($this->getRowBatch() as $rows) {
            foreach ($rows as $columns) {
                $row = [];
                foreach ($columns as $column) {
                    $value = $column->getValue();
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    $handle = $column->getField()?->getHandle() ?? $column->getDescriptor()->getId();

                    $row[$handle] = $value;
                }

                $json = json_encode($row, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT);
                $json = preg_replace('/^/m', '    ', $json);

                fwrite($resource, $json);
                if (++$count < $total) {
                    fwrite($resource, ',');
                }
                fwrite($resource, "\n");
            }
        }

        fwrite($resource, "]\n");
    }
}
