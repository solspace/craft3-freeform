<?php

namespace Solspace\Freeform\Fields\Traits;

use Solspace\Freeform\Attributes\Property\Input\Text;
use Solspace\Freeform\Attributes\Property\Translatable;

trait DefaultTextValueTrait
{
    #[Translatable]
    #[Text(
        label: 'Default value',
        instructions: 'Enter a default value for this field',
    )]
    protected ?string $defaultValue = '';

    public function getDefaultValue(): string
    {
        return $this->defaultValue ?? '';
    }
}
