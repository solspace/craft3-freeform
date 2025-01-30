<?php

namespace Solspace\Freeform\Bundles\Fields\Validation;

use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\ArrayHelper;
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

        $isRequired = $field->isRequired();
        if (!$isRequired) {
            return;
        }

        $value = $field->getValue();
        $isSomeFilled = ArrayHelper::someRecursive($value, fn ($item) => !empty($item));
        if (!$isSomeFilled) {
            $message = $field->getRequiredErrorMessage() ?: Freeform::t('This field is required');

            $field->addError($message);
        }
    }

    public function validateRequiredColumns(ValidateEvent $event): void
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

        if (0 === \count($requiredColumnIndexes)) {
            return;
        }

        $message = Freeform::t($field->getRequiredErrorMessage() ?: 'This field is required');

        $value = $field->getValue();
        $isSomeFilled = ArrayHelper::someRecursive($value, fn ($item) => !empty($item));

        if (!$isSomeFilled) {
            $field->addError($message);

            return;
        }

        foreach ($value as $row) {
            foreach ($requiredColumnIndexes as $columnIndex) {
                if (empty($row[$columnIndex])) {
                    $field->addError($message);

                    return;
                }
            }
        }
    }
}
