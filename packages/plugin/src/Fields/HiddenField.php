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

use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoRenderInterface;

class HiddenField extends TextField implements NoRenderInterface
{
    /**
     * Return the field TYPE.
     */
    public function getType(): string
    {
        return self::TYPE_HIDDEN;
    }

    public function getInputHtml(): string
    {
        $attributes = $this->getCustomAttributes();
        $this->addInputAttribute('class', $attributes->getClass());

        return '<input '
            .$this->getInputAttributesString()
            .$this->getAttributeString('name', $this->getHandle())
            .$this->getAttributeString('type', $this->getType())
            .$this->getAttributeString('id', $this->getIdAttribute())
            .$this->getAttributeString('value', $this->getValue())
            .$attributes->getInputAttributesAsString()
            .'/>';
    }
}
