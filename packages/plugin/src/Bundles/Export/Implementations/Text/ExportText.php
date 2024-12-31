<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Text;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Library\Helpers\StringHelper;

class ExportText extends AbstractSubmissionExport
{
    public static function getLabel(): string
    {
        return 'Text';
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    public function getFileExtension(): string
    {
        return 'txt';
    }

    public function export($resource): void
    {
        foreach ($this->getRowBatch() as $row) {
            foreach ($row as $column) {
                if ($column instanceof FieldInterface) {
                    $value = $column->getValueAsString();
                } else {
                    $value = $column;
                    if ($value instanceof \DateTime) {
                        $value = $value->format('Y-m-d H:i:s');
                    }

                    if (\is_array($value) || \is_object($value)) {
                        $value = StringHelper::implodeRecursively(', ', (array) $value);
                    }
                }

                fwrite($resource, $column->getHandle().': '.$value."\n");
            }

            fwrite($resource, "\n");
        }
    }
}
