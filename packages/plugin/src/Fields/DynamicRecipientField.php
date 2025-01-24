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
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\AbstractExternalOptionsField;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\MultipleValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\ObscureValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\OneLineInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\RecipientInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\MultipleValueTrait;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\OneLineTrait;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\RecipientTrait;

class DynamicRecipientField extends AbstractExternalOptionsField implements RecipientInterface, ObscureValueInterface, MultipleValueInterface, OneLineInterface
{
    use MultipleValueTrait;
    use OneLineTrait;
    use RecipientTrait;

    /** @var bool */
    protected $showAsRadio;

    /** @var bool */
    protected $showAsCheckboxes;

    public static function getFieldType(): string
    {
        return FieldInterface::TYPE_DYNAMIC_RECIPIENTS;
    }

    public function isShowAsSelect(): bool
    {
        return !$this->isShowAsRadio() && !$this->isShowAsCheckboxes();
    }

    public function isShowAsRadio(): bool
    {
        return (bool) $this->showAsRadio;
    }

    public function isShowAsCheckboxes(): bool
    {
        return (bool) $this->showAsCheckboxes;
    }

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return FieldInterface::TYPE_DYNAMIC_RECIPIENTS;
    }

    /**
     * Outputs the HTML of input.
     */
    public function getInputHtml(): string
    {
        if ($this->isShowAsRadio()) {
            return $this->renderAsRadios();
        }

        if ($this->isShowAsCheckboxes()) {
            return $this->renderAsCheckboxes();
        }

        return $this->renderAsSelect();
    }

    public function getValueAsString(bool $optionsAsValues = true): string
    {
        if (!$optionsAsValues) {
            return implode(', ', $this->getActualValue($this->getValue()) ?? []);
        }

        $objectValue = $this->getValue();

        $areIndexValues = true;
        foreach ($objectValue as $value) {
            if (!is_numeric($value)) {
                $areIndexValues = false;
            }
        }

        $returnValues = [];
        foreach ($this->getOptions() as $index => $option) {
            $lookup = $areIndexValues ? $index : $option->getValue();
            if (\in_array($lookup, $objectValue, false)) {
                $returnValues[] = $option->getLabel();
            }
        }

        return implode(', ', $returnValues);
    }

    /**
     * Returns an array value of all possible recipient Email addresses.
     *
     * Either returns an ["email", "email"] array
     * Or an array with keys as recipient names, like ["Jon Doe" => "email", ..]
     */
    public function getRecipients(): array
    {
        /** @var Option[] $options */
        $options = $this->getOptions();
        $value = $this->getValue();
        $recipients = [];

        if (null !== $value) {
            foreach ($options as $index => $option) {
                if (\in_array($index, $value, false)) {
                    $emails = explode(',', $option->getValue());
                    foreach ($emails as $email) {
                        $recipients[] = trim($email);
                    }
                }
            }
        }

        return $recipients;
    }

    /**
     * Return the real value of this field
     * Instead of the obscured one.
     *
     * @param mixed $obscureValue
     *
     * @return mixed
     */
    public function getActualValue($obscureValue)
    {
        $options = $this->options;

        if (\is_array($obscureValue)) {
            $list = [];
            foreach ($obscureValue as $value) {
                if (isset($options[$value])) {
                    $list[] = $options[$value]->getValue();
                }
            }

            return $list;
        }

        if (isset($options[$obscureValue])) {
            return $options[$obscureValue]->getValue();
        }

        foreach ($options as $option) {
            if ($option->getValue() === $obscureValue) {
                return $option->getValue();
            }
        }

        return null;
    }

    public function getContentGqlType(): Type|array
    {
        if ($this->isShowAsCheckboxes()) {
            return Type::listOf(Type::string());
        }

        return Type::string();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $description = $this->getContentGqlDescription();

        if ($this->isShowAsCheckboxes()) {
            $description[] = 'Multiple option values allowed.';
        } else {
            $description[] = 'Single option value allowed.';
        }

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

    /**
     * {@inheritDoc}
     */
    protected function onBeforeInputHtml(): string
    {
        return $this->isOneLine() && !$this->isShowAsSelect() ? '<div class="input-group-one-line">' : '';
    }

    /**
     * {@inheritDoc}
     */
    protected function onAfterInputHtml(): string
    {
        return $this->isOneLine() && !$this->isShowAsSelect() ? '</div>' : '';
    }

    private function renderAsSelect(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        $output = '<select '
            .$this->getInputAttributesString()
            .$this->getAttributeString('name', $this->getHandle())
            .$this->getAttributeString('type', $this->getType())
            .$this->getAttributeString('id', $this->getIdAttribute())
            .$this->getRequiredAttribute()
            .$attributes->getInputAttributesAsString()
            .'>';

        foreach ($this->getOptions() as $index => $option) {
            $output .= '<option value="'.$index.'"'.($option->isChecked() ? ' selected' : '').'>';
            $output .= $this->getForm()->getTranslator()->translate($option->getLabel());
            $output .= '</option>';
        }

        $output .= '</select>';

        return $output;
    }

    private function renderAsRadios(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        $output = '';
        foreach ($this->getOptions() as $index => $option) {
            $output .= '<label>';

            $output .= '<input '
                .$this->getInputAttributesString()
                .$this->getAttributeString('name', $this->getHandle())
                .$this->getAttributeString('type', 'radio')
                .$this->getAttributeString('id', $this->getIdAttribute()."-{$index}")
                .$this->getAttributeString('value', $index)
                .$this->getParameterString('checked', $option->isChecked())
                .$attributes->getInputAttributesAsString()
                .'/>';
            $output .= $this->translate($option->getLabel());
            $output .= '</label>';
        }

        return $output;
    }

    private function renderAsCheckboxes(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        $output = '';
        foreach ($this->getOptions() as $index => $option) {
            $output .= '<label>';

            $output .= '<input '
                .$this->getInputAttributesString()
                .$this->getAttributeString('name', $this->getHandle().'[]')
                .$this->getAttributeString('type', 'checkbox')
                .$this->getAttributeString('id', $this->getIdAttribute()."-{$index}")
                .$this->getAttributeString('value', $index)
                .$this->getParameterString('checked', $option->isChecked())
                .$attributes->getInputAttributesAsString()
                .'/>';
            $output .= $this->translate($option->getLabel());
            $output .= '</label>';
        }

        return $output;
    }
}
