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

namespace Solspace\Freeform\Library\Integrations\DataObjects;

class FieldObject implements \JsonSerializable
{
    public const TYPE_STRING = 'string';
    public const TYPE_ARRAY = 'array';
    public const TYPE_NUMERIC = 'numeric';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_TIMESTAMP = 'timestamp';
    public const TYPE_MICROTIME = 'microtime';
    public const TYPE_BOOLEAN = 'boolean';

    /** @var string */
    private $handle;

    /** @var string */
    private $label;

    /** @var bool */
    private $required;

    /** @var string */
    private $type;

    /**
     * @param string $handle
     * @param string $label
     * @param string $type
     * @param bool   $required
     */
    public function __construct($handle, $label, $type, $required = false)
    {
        $this->handle = $handle;
        $this->label = $label;
        $this->type = $type;
        $this->required = (bool) $required;
    }

    public static function getTypes(): array
    {
        return [self::TYPE_STRING, self::TYPE_NUMERIC, self::TYPE_BOOLEAN, self::TYPE_ARRAY];
    }

    public static function getDefaultType(): string
    {
        return self::TYPE_STRING;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isRequired(): bool
    {
        return (bool) $this->required;
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            'handle' => $this->getHandle(),
            'label' => $this->getLabel(),
            'required' => $this->isRequired(),
        ];
    }
}
