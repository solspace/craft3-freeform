<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Csv;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
use Solspace\Freeform\Bundles\Export\Interfaces\StringValueExportInterface;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;

class ExportCsv extends AbstractSubmissionExport implements StringValueExportInterface
{
    public static function getLabel(): string
    {
        return 'CSV';
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }

    public function getFileExtension(): string
    {
        return 'csv';
    }

    public function export($resource): void
    {
        $columnLabels = $this->prepareColumnLabels();
        if (empty($columnLabels)) {
            return;
        }

        $this->writeToFile($resource, $columnLabels);

        foreach ($this->getRowBatch() as $rows) {
            foreach ($rows as $columns) {
                $values = [];
                $extraRows = 0;
                foreach ($columns as $column) {
                    $field = $column->getField();
                    $value = $column->getValue();

                    if ($field instanceof TableField) {
                        $extraRows = max($extraRows, \count($value) - 1);
                        foreach ($this->extractTableRow(0, $value, $field) as $tableColumns) {
                            $values[] = $tableColumns;
                        }
                    } else {
                        $values[] = $value;
                    }
                }

                $this->writeToFile($resource, $values);

                if ($extraRows) {
                    for ($i = 1; $i <= $extraRows; ++$i) {
                        $values = [];
                        foreach ($columns as $column) {
                            $field = $column->getField();
                            $value = $column->getValue();

                            if ($field instanceof FieldInterface) {
                                if ($field instanceof TableField) {
                                    foreach ($this->extractTableRow($i, $value, $field) as $tableColumns) {
                                        $values[] = $tableColumns;
                                    }
                                } else {
                                    $values[] = '';
                                }
                            } else {
                                $values[] = '';
                            }
                        }

                        $this->writeToFile($resource, $values);
                    }
                }
            }
        }
    }

    protected function writeToFile($resource, array $values): void
    {
        fputcsv($resource, $values);
    }

    private function extractTableRow(int $rowIndex, array $tableValues, TableField $field): array
    {
        $values = [];

        $layout = $field->getTableLayout();
        foreach ($layout as $index => $column) {
            $tableColumnValue = $tableValues[$rowIndex][$index] ?? null;

            $values[] = $tableColumnValue;
        }

        return $values;
    }

    private function prepareColumnLabels(): array
    {
        $form = $this->getForm();
        $descriptors = $this->getFieldDescriptors();
        $isHandlesAsNames = $this->getSettings()->isHandlesAsNames();

        $labels = [];
        foreach ($descriptors as $descriptor) {
            if (!$descriptor->isUsed()) {
                continue;
            }

            $field = $form->get($descriptor->getId());
            if (!$field) {
                $labels[] = $isHandlesAsNames ? $descriptor->getId() : $descriptor->getLabel();

                continue;
            }

            if ($field instanceof TableField) {
                foreach ($field->getTableLayout() as $layout) {
                    $labels[] = $layout->label ?? '-';
                }
            } else {
                $labels[] = $isHandlesAsNames ? $field->getHandle() : $field->getLabel();
            }
        }

        return $labels;
    }
}
