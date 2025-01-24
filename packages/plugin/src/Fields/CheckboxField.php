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

namespace Solspace\Freeform\Fields;

use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\SingleValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\SingleValueTrait;
use Twig\Markup;

class CheckboxField extends AbstractField implements SingleValueInterface, InputOnlyInterface
{
    use SingleValueTrait;

    /** @var bool */
    protected $checked;

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_CHECKBOX;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * Outputs the HTML of input.
     */
    public function getInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();

        $output = '<input '
            .$this->getInputAttributesString()
            .$this->getAttributeString('name', $this->getHandle())
            .$this->getAttributeString('type', FieldInterface::TYPE_HIDDEN)
            .$this->getAttributeString('value', '')
            .$attributes->getInputAttributesAsString()
            .'/>';

        $output .= $this->getSingleInputHtml();

        return $output;
    }

    public function getSingleInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        return '<input '
            .$this->getInputAttributesString()
            .$this->getAttributeString('name', $this->getHandle())
            .$this->getAttributeString('type', $this->getType())
            .$this->getAttributeString('id', $this->getIdAttribute())
            .$this->getAttributeString('value', $this->getDefaultValue())
            .$this->getParameterString('checked', (bool) $this->getValue())
            .$this->getRequiredAttribute()
            .$attributes->getInputAttributesAsString()
            .'/>';
    }

    public function renderSingleInput(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        return $this->renderRaw($this->getSingleInputHtml());
    }

    public function getContentGqlMutationArgumentType(): Type|array
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

    /**
     * Output something before an input HTML is output.
     */
    protected function onBeforeInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addLabelAttribute('class', $attributes->getLabelClass());

        return '<label'
            .$this->getLabelAttributesString()
            .'>';
    }

    /**
     * Output something after an input HTML is output.
     */
    protected function onAfterInputHtml(): string
    {
        $output = $this->getLabel();
        $output .= '</label>';

        return $output;
    }
}
