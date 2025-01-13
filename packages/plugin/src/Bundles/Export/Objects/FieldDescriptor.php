<?php

namespace Solspace\Freeform\Bundles\Export\Objects;

class FieldDescriptor
{
    public function __construct(
        private int|string $id,
        private string $label,
        private bool $used = true,
    ) {}

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }
}
