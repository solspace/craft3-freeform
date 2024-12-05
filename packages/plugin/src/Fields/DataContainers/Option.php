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

namespace Solspace\Freeform\Fields\DataContainers;

class Option
{
    public function __construct(
        private string $label,
        private string $value,
        private bool $checked = false
    ) {}

    public function __toString(): string
    {
        return $this->getLabel();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function isChecked(): bool
    {
        return $this->checked;
    }
}
