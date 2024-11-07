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

namespace Solspace\Freeform\Library\Integrations\DataObjects;

class FieldObject
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

    private FieldOptionCollection $options;

    public function __construct(
        private string $handle,
        private string $label,
        private string $type,
        private string $category,
        private bool $required = false,
        ?array $options = null,
    ) {
        $this->options = new FieldOptionCollection();

        if (null !== $options) {
            foreach ($options as $option) {
                if ($option instanceof FieldObjectOption) {
                    $this->options->add($option);
                } else {
                    $this->options->add(
                        new FieldObjectOption(
                            $option['key'],
                            $option['label'],
                            $option['description'] ?? null
                        )
                    );
                }
            }
        }
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function isRequired(): bool
    {
        return (bool) $this->required;
    }

    public function getOptions(): ?FieldOptionCollection
    {
        return $this->options;
    }
}
