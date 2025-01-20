<?php

namespace Solspace\Freeform\Bundles\Fields\Validation;

use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class RequiredFieldValidation extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validate']
        );
    }

    public function validate(ValidateEvent $event): void
    {
        $field = $event->getField();
        $value = $field->getValue();

        if (!$field->isRequired()) {
            return;
        }

        if ($field instanceof FileUploadInterface) {
            return;
        }

        if ($field instanceof TableField) {
            return;
        }

        if (\is_string($value)) {
            $value = trim($value);
        }

        if (\is_array($value)) {
            $value = array_filter($value);
        }

        if ('' === $value || null === $value || [] === $value) {
            $message = $field->getRequiredErrorMessage() ?: Freeform::t('This field is required');

            $field->addError($message);
        }
    }
}
