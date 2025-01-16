<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Text;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
use Solspace\Freeform\Bundles\Export\Interfaces\StringValueExportInterface;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Library\Helpers\StringHelper;

class ExportText extends AbstractSubmissionExport implements StringValueExportInterface
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
        $isHandlesAsNames = $this->getSettings()->isHandlesAsNames();

        foreach ($this->getRowBatch() as $rows) {
            foreach ($rows as $columns) {
                foreach ($columns as $column) {
                    $field = $column->getField();
                    $value = $column->getValue();

                    $id = $column->getDescriptor()->getId();
                    $label = $column->getDescriptor()->getLabel();

                    if ($field instanceof FieldInterface) {
                        if ($field instanceof TableField) {
                            $value = StringHelper::implodeRecursively(', ', $field->getValue());
                        } else {
                            $value = $field->getValueAsString();
                        }
                    }

                    $descriptor = $isHandlesAsNames ? $id : $label;

                    fwrite($resource, $descriptor.': '.trim($value)."\n");
                }

                fwrite($resource, "\n");
            }
        }
    }
}
