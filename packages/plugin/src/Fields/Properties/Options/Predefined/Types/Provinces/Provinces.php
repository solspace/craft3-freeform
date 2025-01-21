<?php

namespace Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Provinces;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input\Select;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\PredefinedSourceTypeInterface;

class Provinces implements PredefinedSourceTypeInterface
{
    private const LANG_EN = 'en';
    private const LANG_FR = 'fr';
    private const LANG_BI = 'bi';

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

    #[Select(
        label: 'Language',
        options: [
            self::LANG_EN => 'English',
            self::LANG_FR => 'French',
            self::LANG_BI => 'Bilingual',
        ],
    )]
    private string $language = self::LANG_EN;

    public function getName(): string
    {
        return 'Canadian Provinces';
    }

    public function generateOptions(): OptionCollection
    {
        static $countries;
        if (null === $countries) {
            $countries = json_decode(file_get_contents(__DIR__.'/provinces.json'), true);
        }

        $collection = new OptionCollection();
        foreach ($countries as $code => $data) {
            $collection->add(
                self::DISPLAY_FULL === $this->value ? $data[$this->language] : $code,
                self::DISPLAY_FULL === $this->label ? $data[$this->language] : $code,
            );
        }

        return $collection;
    }
}
