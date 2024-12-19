<?php

namespace Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Numbers;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input\Integer;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\PredefinedSourceTypeInterface;

class Numbers implements PredefinedSourceTypeInterface
{
    #[Integer(
        label: 'First Number',
        instructions: 'The first number to start the range from.',
    )]
    private ?int $first = 0;

    #[Integer(
        label: 'Last Number',
        instructions: 'The last number to end the range with.',
    )]
    private ?int $second = 20;

    #[Integer(
        instructions: 'Step for filling the number values.',
        unsigned: true,
    )]
    private ?int $step = 1;

    public function getName(): string
    {
        return 'Numbers';
    }

    public function generateOptions(): OptionCollection
    {
        $collection = new OptionCollection();

        $first = $this->first ?? 0;
        $last = $this->second ?? 20;
        $step = $this->step ?: 1;

        foreach (range($first, $last, $step) as $i) {
            $collection->add($i, $i);
        }

        return $collection;
    }
}
