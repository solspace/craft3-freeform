<?php

namespace Solspace\Freeform\Fields\Implementations;

use craft\gql\types\Number as NumberType;
use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\Interfaces\NumericInterface;

#[Type(
    name: 'Number',
    typeShorthand: 'number',
    iconPath: __DIR__.'/Icons/number.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/text.ejs',
)]
class NumberField extends TextField implements NumericInterface
{
    #[Input\Boolean('Allow negative numbers')]
    protected bool $allowNegative = false;

    #[Input\MinMax(
        label: 'Min/Max Values',
        instructions: 'The minimum and/or maximum numeric value this field is allowed to have (optional).',
    )]
    protected ?array $minMaxValues = [null, null];

    #[Input\Integer(
        instructions: 'The number of decimal places allowed.',
        placeholder: 'Leave blank for no decimals',
        min: 0,
    )]
    protected ?int $decimalCount = 0;

    #[Input\Integer(
        instructions: 'The step',
        min: 0,
    )]
    protected ?int $step = 1;

    public function getValue(): mixed
    {
        $value = parent::getValue();
        $value = str_replace(',', '.', $value);
        if (is_numeric($value)) {
            $value += 0;
        }

        return $value;
    }

    public function getMinMaxValues(): array
    {
        if (!\is_array($this->minMaxValues)) {
            return [null, null];
        }

        return $this->minMaxValues;
    }

    public function getType(): string
    {
        return self::TYPE_NUMBER;
    }

    public function getMinValue(): ?int
    {
        [$min] = $this->getMinMaxValues();

        return $min;
    }

    public function getMaxValue(): ?int
    {
        [, $max] = $this->getMinMaxValues();

        return $max;
    }

    public function getDecimalCount(): int
    {
        return $this->decimalCount ?? 0;
    }

    public function isAllowNegative(): bool
    {
        return $this->allowNegative;
    }

    public function getStep(): float
    {
        return $this->step ?? 1;
    }

    public function getContentGqlType(): array|GQLType
    {
        return NumberType::getType();
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();

        if ($this->isAllowNegative()) {
            $description[] = 'Negative numbers are allowed.';
        } else {
            $description[] = 'Only positive numbers are allowed.';
        }

        if (!empty($this->getMinValue())) {
            $description[] = 'Min value: '.$this->getMinValue().'.';
        }

        if (!empty($this->getMaxValue())) {
            $description[] = 'Max value: '.$this->getMaxValue().'.';
        }

        if (!empty($this->getStep())) {
            $description[] = 'Step value: '.$this->getStep().'.';
        }

        if (!empty($this->getDecimalCount())) {
            $description[] = $this->getDecimalCount().' decimal places are allowed.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    /**
     * Outputs the HTML of input.
     */
    protected function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', 'number')
            ->setIfEmpty('id', $this->getIdAttribute())
            ->setIfEmpty('min', $this->getMinValue())
            ->setIfEmpty('max', $this->getMaxValue())
            ->setIfEmpty('step', $this->getStep())
            ->setIfEmpty('placeholder', $this->translate('placeholder', $this->getPlaceholder()))
            ->setIfEmpty('value', $this->getValue())
            ->set($this->getRequiredAttribute())
        ;

        return '<input'.$attributes.' />';
    }
}
