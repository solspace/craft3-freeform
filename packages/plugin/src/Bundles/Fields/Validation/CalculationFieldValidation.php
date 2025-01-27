<?php

namespace Solspace\Freeform\Bundles\Fields\Validation;

use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\CalculationField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use yii\base\Event;

class CalculationFieldValidation extends FeatureBundle
{
    private const GET_VARIABLES_PATTERN = '/field:([a-zA-Z0-9_]+)/u';
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validate']
        );

        // Initialize ExpressionLanguage with sqrt function
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->register(
            'sqrt',
            // Compiler function
            function ($value) {
                return \sprintf('sqrt(%s)', $value);
            },
            // Evaluator function
            function ($arguments, $value) {
                if (!is_numeric($value)) {
                    return $value;
                }

                return sqrt((float) $value);
            }
        );
    }

    public function validate(ValidateEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof CalculationField) {
            return;
        }

        $form = $event->getForm();
        $valueOrdination = $this->valueOrdination($field->getValue());
        $calculationLogic = $field->getCalculations();
        $decimalCount = $field->getDecimalCount();
        $canRender = $field->canRender();

        preg_match_all(self::GET_VARIABLES_PATTERN, $calculationLogic, $matches);
        $variables = $matches[1];

        $variablesWithValue = [];

        foreach ($variables as $variable) {
            $calculationLogic = str_replace("field:{$variable}", $variable, $calculationLogic);

            $fieldValue = $form->get($variable)->getValue();

            if (null !== $fieldValue && '' !== trim($fieldValue)) {
                $variablesWithValue[$variable] = $this->valueOrdination($fieldValue);
            } else {
                $field->addError(Freeform::t('Variable "{variable}" is missing a value', ['variable' => $variable]));

                return;
            }
        }

        try {
            $result = $this->expressionLanguage->evaluate($calculationLogic, $variablesWithValue);
            if (null !== $decimalCount && $decimalCount >= 0) {
                $result = number_format($result, $decimalCount);
            }

            if ($valueOrdination != $result) {
                $errorMessage = Freeform::t('Incorrectly calculated value');

                if ($canRender) {
                    $field->addError($errorMessage);
                } else {
                    $form->addError($errorMessage);
                }
            }

            if (!$canRender) {
                $field->setValue($result);
            }
        } catch (\Throwable $e) {
            $field->addError(Freeform::t('Error in calculation'));
        }
    }

    private function valueOrdination($value): bool|float|string
    {
        $lowercaseValue = strtolower($value);

        if ('true' === $lowercaseValue) {
            return true;
        }
        if ('false' === $lowercaseValue) {
            return false;
        }

        return is_numeric($value) ? (float) $value : $value;
    }
}
