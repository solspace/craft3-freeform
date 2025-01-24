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
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\DefaultFieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\MailingListInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\RememberPostedValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\SingleValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\MailingListTrait;
use Solspace\Freeform\Library\Composer\Components\Fields\Traits\SingleValueTrait;

class MailingListField extends AbstractField implements DefaultFieldInterface, NoStorageInterface, SingleValueInterface, InputOnlyInterface, MailingListInterface, RememberPostedValueInterface
{
    use MailingListTrait;
    use SingleValueTrait;

    /** @var array */
    protected $mapping;

    /** @var bool */
    protected $hidden;

    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return FieldInterface::TYPE_MAILING_LIST;
    }

    /**
     * MailingList uses its HASH as the Handle.
     */
    public function getHandle(): string
    {
        return $this->getHash();
    }

    public function getMapping(): array
    {
        return $this->mapping;
    }

    public function isHidden(): bool
    {
        return (bool) $this->hidden;
    }

    /**
     * Outputs the HTML of input.
     */
    public function getInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());
        $isSelected = (bool) $this->getValue();

        if ($this->isHidden()) {
            return '<input '
                .$this->getInputAttributesString()
                .$this->getAttributeString('name', $this->getHash())
                .$this->getAttributeString('type', 'hidden')
                .$this->getAttributeString('id', $this->getIdAttribute())
                .$this->getAttributeString('value', 1, false)
                .$this->getRequiredAttribute()
                .$attributes->getInputAttributesAsString()
                .'/>';
        }

        return '<input '
            .$this->getInputAttributesString()
            .$this->getAttributeString('name', $this->getHash())
            .$this->getAttributeString('type', 'checkbox')
            .$this->getAttributeString('id', $this->getIdAttribute())
            .$this->getAttributeString('value', 1, false)
            .$this->getRequiredAttribute()
            .$attributes->getInputAttributesAsString()
            .($isSelected ? 'checked ' : '')
            .'/>';
    }

    public function getContentGqlHandle(): string
    {
        return 'mailingList_'.$this->getHash();
    }

    public function getContentGqlType(): Type|array
    {
        return Type::int();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $description = $this->getContentGqlDescription();
        $description[] = 'Used by '.$this->getLabel().' field.';
        $description[] = 'Single option value allowed.';
        $description[] = 'Options include 0, 1.';

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
        if ($this->isHidden()) {
            return '';
        }

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
        if ($this->isHidden()) {
            return '';
        }

        $output = $this->getLabel();
        $output .= '</label>';

        return $output;
    }
}
