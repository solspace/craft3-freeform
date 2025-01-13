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
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Interfaces\BooleanInterface;
use Solspace\Freeform\Fields\Interfaces\DefaultValueInterface;
use Solspace\Freeform\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Fields\Interfaces\NoLabelInterface;
use Solspace\Freeform\Fields\Traits\DefaultTextValueTrait;
use Solspace\Freeform\Library\Attributes\Attributes;
use Twig\Markup;

/**
 * @extends AbstractField<boolean>
 */
#[Type(
    name: 'Checkbox',
    typeShorthand: 'checkbox',
    iconPath: __DIR__.'/Icons/checkbox.svg',
    previewTemplatePath: __DIR__.'/PreviewTemplates/checkbox.ejs',
)]
class CheckboxField extends AbstractField implements InputOnlyInterface, NoLabelInterface, BooleanInterface, DefaultValueInterface
{
    use DefaultTextValueTrait;

    #[Input\Boolean('Checked by default')]
    protected bool $checkedByDefault = false;

    protected ?bool $checked = null;

    public function getType(): string
    {
        return self::TYPE_CHECKBOX;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue ?: 'yes';
    }

    public function isCheckedByDefault(): bool
    {
        return $this->checkedByDefault;
    }

    public function isChecked(): ?bool
    {
        return $this->checked;
    }

    public function setChecked(bool $checked): self
    {
        $this->checked = $checked;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getInputHtml(): string
    {
        $attributes = new Attributes([
            'name' => $this->getHandle(),
            'type' => FieldInterface::TYPE_HIDDEN,
            'value' => '',
        ]);

        $output = '<input'.$attributes.' />';
        $output .= $this->getSingleInputHtml();

        return $output;
    }

    public function getSingleInputHtml(): string
    {
        $attributes = $this->getAttributes()->getInput()
            ->clone()
            ->setIfEmpty('name', $this->getHandle())
            ->setIfEmpty('type', $this->getType())
            ->setIfEmpty('id', $this->getIdAttribute())
            ->setIfEmpty('value', $this->getDefaultValue())
            ->setIfEmpty('checked', $this->isChecked())
            ->setIfEmpty($this->getRequiredAttribute())
        ;

        return '<input '.$attributes.' />';
    }

    public function renderSingleInput(): Markup
    {
        return $this->renderRaw($this->getSingleInputHtml());
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Single option value allowed.';
        $description[] = 'Option value is "'.$this->getDefaultValue().'".';

        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    protected function onBeforeInputHtml(): string
    {
        return '<label'.$this->getAttributes()->getLabel().'>';
    }

    protected function onAfterInputHtml(): string
    {
        $output = $this->getLabel();
        $output .= '</label>';

        return $output;
    }
}
