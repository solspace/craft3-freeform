<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields\Implementations;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input\Hidden;
use Solspace\Freeform\Fields\BaseGeneratedOptionsField;
use Solspace\Freeform\Fields\Interfaces\DefaultValueInterface;
use Solspace\Freeform\Fields\Interfaces\OneLineInterface;
use Solspace\Freeform\Fields\Traits\OneLineTrait;

#[Type(
    name: 'Radios',
    typeShorthand: 'radios',
    iconPath: __DIR__.'/Icons/radios.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/radios.ejs',
)]
class RadiosField extends BaseGeneratedOptionsField implements OneLineInterface, DefaultValueInterface
{
    use OneLineTrait;

    #[Hidden]
    protected string $defaultValue = '';

    public function getType(): string
    {
        return self::TYPE_RADIO_GROUP;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    /**
     * Outputs the HTML of input.
     */
    public function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', 'radio')
            ->set($this->getRequiredAttribute())
            ->setIfEmpty('value', $this->getValue())
        ;

        $output = '';

        foreach ($this->getOptions() as $index => $option) {
            if ($option instanceof OptionCollection) {
                continue;
            }

            $inputAttributes = $attributes
                ->clone()
                ->replace('id', $this->getIdAttribute().'-'.$index)
                ->replace('value', $option->getValue())
                ->replace('checked', $option->getValue() === $this->getValue())
            ;

            $output .= '<label>';
            $output .= '<input'.$inputAttributes.' />';
            $output .= $this->translateOption('optionConfiguration', $option->getValue(), $option->getLabel());
            $output .= '</label>';
        }

        return $output;
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Single option value allowed.';

        $values = [];

        foreach ($this->getOptions() as $option) {
            $values[] = '"'.$option->getValue().'"';
        }

        if (!empty($values)) {
            $description[] = 'Options include '.implode(', ', $values).'.';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    protected function onBeforeInputHtml(): string
    {
        return $this->isOneLine() ? '<div class="input-group-one-line">' : '';
    }

    protected function onAfterInputHtml(): string
    {
        return $this->isOneLine() ? '</div>' : '';
    }
}
