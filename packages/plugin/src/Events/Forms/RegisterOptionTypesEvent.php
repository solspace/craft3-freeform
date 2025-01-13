<?php

namespace Solspace\Freeform\Events\Forms;

use Solspace\Freeform\Fields\Properties\Options\OptionTypeProviderInterface;
use yii\base\Event;

class RegisterOptionTypesEvent extends Event
{
    public function __construct(private array $types)
    {
        parent::__construct();
    }

    public function addType(OptionTypeProviderInterface $type): self
    {
        $this->types[] = $type;

        return $this;
    }

    public function getTypes(): array
    {
        return $this->types;
    }
}
