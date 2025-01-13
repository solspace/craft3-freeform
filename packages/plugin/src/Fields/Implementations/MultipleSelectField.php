<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Fields\Implementations;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Input\Hidden;
use Solspace\Freeform\Fields\BaseGeneratedOptionsField;
use Solspace\Freeform\Fields\Interfaces\DefaultValueInterface;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;
use Solspace\Freeform\Fields\Traits\MultipleValueTrait;

#[Type(
    name: 'Multi-Select',
    typeShorthand: 'multiple-select',
    iconPath: __DIR__.'/Icons/multi-select.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/multiple-select.ejs',
)]
class MultipleSelectField extends BaseGeneratedOptionsField implements MultiValueInterface, DefaultValueInterface
{
    use MultipleValueTrait;

    #[Hidden]
    protected ?array $defaultValue = [];

    public function getType(): string
    {
        return self::TYPE_MULTIPLE_SELECT;
    }

    public function getDefaultValue(): array
    {
        return $this->defaultValue ?? [];
    }

    public function getInputHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle().'[]')
            ->setIfEmpty('id', $this->getIdAttribute())
            ->set('multiple', true)
            ->set($this->getRequiredAttribute())
        ;

        $output = '<select'.$attributes.'>';

        foreach ($this->getOptions() as $option) {
            $isSelected = \in_array($option->getValue(), $this->getValue());

            $output .= '<option value="'.$option->getValue().'"'.($isSelected ? ' selected' : '').'>';
            $output .= $this->translate('label', $option->getLabel());
            $output .= '</option>';
        }

        $output .= '</select>';

        return $output;
    }

    public function getContentGqlType(): array|GQLType
    {
        return GQLType::listOf(GQLType::string());
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Multiple option values allowed.';

        $values = [];

        foreach ($this->getOptions() as $option) {
            $values[] = '"'.$option->getValue().'"';
        }

        if (!empty($values)) {
            $description[] = 'Options include ['.implode(', ', $values).'].';
        }

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }
}
