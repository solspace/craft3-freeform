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

namespace Solspace\Freeform\Library\Composer\Components\Fields\DataContainers;

class Option implements \JsonSerializable
{
    /** @var string */
    private $label;

    /** @var string */
    private $value;

    /** @var bool */
    private $checked;

    /**
     * Option constructor.
     */
    public function __construct(string $label, string $value, bool $checked = false)
    {
        $this->label = $label;
        $this->value = $value;
        $this->checked = $checked;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'label' => $this->getLabel(),
            'value' => $this->getValue(),
        ];
    }
}
