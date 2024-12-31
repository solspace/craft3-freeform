<?php

namespace Solspace\Freeform\Bundles\Export\Objects;

use Solspace\Freeform\Fields\FieldInterface;

class Column
{
    public function __construct(
        private int $index,
        private FieldDescriptor $descriptor,
        private ?FieldInterface $field,
        private mixed $value
    ) {}

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getDescriptor(): FieldDescriptor
    {
        return $this->descriptor;
    }

    public function getField(): ?FieldInterface
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
