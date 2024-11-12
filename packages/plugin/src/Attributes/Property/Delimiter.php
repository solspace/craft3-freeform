<?php

namespace Solspace\Freeform\Attributes\Property;

use Solspace\Freeform\Library\Serialization\Normalizers\CustomNormalizerInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Delimiter implements CustomNormalizerInterface
{
    public function __construct(
        public ?string $name = null,
        public ?string $icon = null,
    ) {}

    public function normalize(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
        ];
    }
}
