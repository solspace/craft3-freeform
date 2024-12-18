<?php

namespace Solspace\Freeform\Bundles\Export\Objects;

use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\FieldInterface;

class Column
{
    /** @var int */
    private $index;

    /** @var string */
    private $label;

    /** @var string */
    private $handle;

    /** @var null|AbstractField|FieldInterface */
    private $field;

    /** @var mixed */
    private $value;

    /**
     * Column constructor.
     *
     * @param AbstractField|FieldInterface $field
     * @param mixed                        $value
     */
    public function __construct(int $index, string $label, string $handle, $field, $value)
    {
        $this->index = $index;
        $this->label = $label;
        $this->handle = $handle;
        $this->field = $field;
        $this->value = $value;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * @return null|AbstractField|FieldInterface
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
