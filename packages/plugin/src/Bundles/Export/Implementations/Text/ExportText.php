<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Text;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
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

    public function export(): string
    {
        $output = '';
        foreach ($this->getRowBatch() as $rowIndex => $row) {
            foreach ($row as $column) {
                $value = $column->getValue();
                if (\is_array($value) || \is_object($value)) {
                    $value = StringHelper::implodeRecursively(', ', (array) $value);
                }

                $output .= $column->getHandle().': '.$value."\n";
            }

            $output .= "\n";
        }

        return $output;
    }
}
