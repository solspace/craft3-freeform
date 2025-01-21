<?php

namespace Solspace\Freeform\Fields\Properties\Options\Predefined\Types\States;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input\Boolean;
use Solspace\Freeform\Attributes\Property\Input\Select;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\PredefinedSourceTypeInterface;

class States implements PredefinedSourceTypeInterface
{
    #[Select(
        label: 'Option Label',
        options: [
            self::DISPLAY_ABBREVIATED => 'Abbreviated',
            self::DISPLAY_FULL => 'Full',
        ],
    )]
    private string $label = self::DISPLAY_FULL;

    #[Select(
        label: 'Option Value',
        options: [
            self::DISPLAY_ABBREVIATED => 'Abbreviated',
            self::DISPLAY_FULL => 'Full',
        ],
    )]
    private string $value = self::DISPLAY_ABBREVIATED;

    #[Boolean]
    private bool $includeTerritories = false;

    public function getName(): string
    {
        return 'USA States';
    }

    public function generateOptions(): OptionCollection
    {
        static $states;
        if (null === $states) {
            $states = json_decode(file_get_contents(__DIR__.'/states.json'), true);
        }

        static $statesTerritories;
        if (null === $statesTerritories) {
            $statesTerritories = json_decode(file_get_contents(__DIR__.'/states-territories.json'), true);
        }

        $list = $this->includeTerritories ? $statesTerritories : $states;

        $collection = new OptionCollection();
        foreach ($list as $code => $name) {
            $collection->add(
                self::DISPLAY_FULL === $this->value ? $name : $code,
                self::DISPLAY_FULL === $this->label ? $name : $code,
            );
        }

        return $collection;
    }
}
