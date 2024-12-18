<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Csv;

use Solspace\Freeform\Bundles\Export\AbstractSubmissionExport;
use Solspace\Freeform\Bundles\Export\Interfaces\StringValueExportInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Library\Helpers\StringHelper;

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

    public function export(): mixed
    {
        $columnLabels = $this->prepareColumnLabels();
        if (empty($columnLabels)) {
            return '';
        }

        $output = [$this->prepareColumnLabels()];
        foreach ($this->getRowBatch() as $rows) {
            foreach ($rows as $row) {
                $this->handleRow($row, $output);
                $output[] = StringHelper::implodeRecursively(',', $row);
            }
        }

        // dd($output);

        return StringHelper::implodeRecursively("\n", $output);
        //        $output = [];
        //        foreach ($this->getValuesAsArray() as $row) {
        //            $rowData = [];
        //            foreach ($row as $value) {
        //                if ($value) {
        //                    $rowData[] = '"'.str_replace('"', '""', $value).'"';
        //                } else {
        //                    $rowData[] = null;
        //                }
        //            }
        //
        //            $output[] = StringHelper::implodeRecursively(',', $rowData);
        //        }
        //
        //        return StringHelper::implodeRecursively("\n", $output);
    }

    protected function getValuesAsArray(): array
    {
        $output = [];
        foreach ($this->getRowBatch() as $rowIndex => $row) {
            if (0 === $rowIndex) {
                $labels = [];
                foreach ($row as $column) {
                    if ($column->getField() instanceof TableField) {
                        foreach ($column->getField()->getTableLayout() as $layout) {
                            $labels[] = $layout->label ?? '-';
                        }
                    } else {
                        $labels[] = $this->isHandlesAsNames() ? $column->getHandle() : $column->getLabel();
                    }
                }

                $output[] = $labels;
            }

            $values = [];
            foreach ($row as $column) {
                $value = $column->getValue();
                $field = $column->getField();

                if ($field && $field instanceof TableField) {
                    $values = array_merge($values, $this->extractTableRow(0, $value ?? [], $field));
                } else {
                    if ('' !== $value && null !== $value) {
                        if (\is_array($value) || \is_object($value)) {
                            $value = StringHelper::implodeRecursively(', ', (array) $value);
                        }

                        if ($field) {
                            if ($field instanceof TextareaField && $this->isRemoveNewLines()) {
                                $value = trim(preg_replace('/\s+/', ' ', $value));
                            }
                        }
                    }

                    $values[] = $value;
                }
            }

            $output[] = $values;

            if ($row->hasMultiDimensionalFields() && $row->getArtificialRowCount()) {
                for ($i = 1; $i <= $row->getArtificialRowCount(); ++$i) {
                    $values = [];
                    foreach ($row as $column) {
                        $field = $column->getField();
                        $value = $column->getValue();
                        if ($field instanceof TableField) {
                            $values = array_merge($values, $this->extractTableRow($i, $value ?? [], $field));
                        } else {
                            $values[] = null;
                        }
                    }

                    $output[] = $values;
                }
            }
        }

        return $output;
    }

    private function handleRow(array $row, array $output): void {}

    private function prepareColumnLabels(): string
    {
        $form = $this->getForm();
        $descriptors = $this->getFieldDescriptors();

        $labels = [];
        foreach ($descriptors as $descriptor) {
            $field = $form->get($descriptor->getId());
            if (!is_numeric($descriptor->getId()) || !$field) {
                $labels[] = $descriptor->getLabel();

                continue;
            }

            if ($field instanceof TableField) {
                foreach ($field->getTableLayout() as $layout) {
                    $labels[] = $layout->label ?? '-';
                }
            } else {
                $labels[] = $this->getSettings()->isHandlesAsNames() ? $field->getHandle() : $field->getLabel();
            }
        }

        $labels = array_map(fn ($label) => '"'.$label.'"', $labels);

        return StringHelper::implodeRecursively(',', $labels);
    }

    private function extractTableRow(int $rowIndex, array $tableValues, TableField $field): array
    {
        $values = [];
        foreach ($field->getTableLayout() as $index => $layout) {
            $tableColumnValue = $tableValues[$rowIndex][$index] ?? null;

            $values[] = $tableColumnValue;
        }

        return $values;
    }
}
