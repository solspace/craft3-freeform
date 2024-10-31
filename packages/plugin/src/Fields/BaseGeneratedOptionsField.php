<?php

namespace Solspace\Freeform\Fields;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionsTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Translatable;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Fields\Interfaces\GeneratedOptionsInterface;
use Solspace\Freeform\Fields\Properties\Options\OptionsConfigurationInterface;

/**
 * @implements \IteratorAggregate<int, Option|OptionCollection>
 */
abstract class BaseGeneratedOptionsField extends BaseOptionsField implements GeneratedOptionsInterface
{
    #[Translatable]
    #[ValueTransformer(OptionsTransformer::class)]
    #[Input\Options(
        label: 'Options Editor',
        instructions: 'Define your options',
    )]
    protected ?OptionsConfigurationInterface $optionConfiguration = null;

    public function getOptionConfiguration(): ?OptionsConfigurationInterface
    {
        return $this->optionConfiguration;
    }

    public function getOptions(): OptionCollection
    {
        return $this->optionConfiguration->getOptions();
    }
}
