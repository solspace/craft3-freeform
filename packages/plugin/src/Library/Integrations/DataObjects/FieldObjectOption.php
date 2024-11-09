<?php

namespace Solspace\Freeform\Library\Integrations\DataObjects;

class FieldObjectOption
{
    public function __construct(
        public int|string $key,
        public string $label,
        public ?string $description = null
    ) {}
}
