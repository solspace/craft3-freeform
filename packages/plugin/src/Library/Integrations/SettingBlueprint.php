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

namespace Solspace\Freeform\Library\Integrations;

class SettingBlueprint
{
    public const TYPE_INTERNAL = 'internal';
    public const TYPE_CONFIG = 'config';
    public const TYPE_TEXT = 'text';
    public const TYPE_AUTO = 'auto';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_BOOL = 'bool';

    /** @var string */
    private $type;

    /** @var string */
    private $handle;

    /** @var string */
    private $label;

    /** @var string */
    private $instructions;

    /** @var bool */
    private $required;

    /** @var mixed */
    private $defaultValue;

    /**
     * SettingObject constructor.
     *
     * @param string $type
     * @param string $handle
     * @param string $label
     * @param string $instructions
     * @param bool   $required
     * @param mixed  $defaultValue
     */
    public function __construct(
        $type,
        $handle,
        $label,
        $instructions,
        $required = false,
        $defaultValue = null
    ) {
        $this->type = $type;
        $this->handle = $handle;
        $this->label = $label;
        $this->instructions = $instructions;
        $this->required = (bool) $required;
        $this->defaultValue = $defaultValue;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getInstructions(): string
    {
        return $this->instructions;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return null|mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
}
