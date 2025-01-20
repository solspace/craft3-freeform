<?php

namespace Solspace\Freeform\Bundles\Fields\Validation;

use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class TableValidation extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validateRowLimit']
        );

        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validateRequired']
        );

        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validateRequiredColumns']
        );
    }

    public function validateRowLimit(ValidateEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof TableField) {
            return;
        }

        $value = $field->getValue();
        if (empty($value)) {
            return;
        }

        $maxRows = $field->getMaxRows();
        if (empty($maxRows)) {
            return;
        }

        $rows = \count($value);

        if ($rows > $maxRows) {
            $message = str_replace(
                '{{maxRows}}',
                $maxRows,
                Freeform::t('The maximum number of rows is {{maxRows}}.')
            );

            $field->addError($message);
        }
    }

    public function validateRequired(ValidateEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof TableField) {
            return;
        }

        $layout = $field->getTableLayout();
        $requiredColumnIndexes = [];
        foreach ($layout as $index => $column) {
            if ($column->required) {
                $requiredColumnIndexes[] = $index;
            }
        }

        // If there are any required columns defined, skip this validation
        // it will be taken care of in the column check
        if (0 !== \count($requiredColumnIndexes)) {
            return;
        }

        $value = $field->getValue();
        if (empty($value)) {
            return;
        }

        // filter out all empty values recursively
        $filteredValue = array_filter($value, function ($row) {
            return !empty(array_filter($row));
        });

        if (empty($filteredValue)) {
            $field->addError(Freeform::t('This field is required'));
        }
    }

    public function validateRequiredColumns(ValidateEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof TableField) {
            return;
        }

        $value = $field->getValue();
        if (empty($value)) {
            return;
        }

        $layout = $field->getTableLayout();
        $requiredColumnIndexes = [];
        foreach ($layout as $index => $column) {
            if ($column->required) {
                $requiredColumnIndexes[] = $index;
            }
        }

        if (0 === \count($requiredColumnIndexes)) {
            return;
        }

        foreach ($value as $rowIndex => $row) {
            foreach ($requiredColumnIndexes as $columnIndex) {
                if (empty($row[$columnIndex])) {
                    $field->addError(Freeform::t('One or more required field columns are missing a value'));

                    break;
                }
            }
        }
    }
}
