<?php

namespace Solspace\Freeform\Fields\Properties\Options\Elements\Types;

use Solspace\Freeform\Events\Forms\RegisterOptionTypesEvent;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\Assets\Assets;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\Categories\Categories;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\Entries\Entries;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\Tags\Tags;
use Solspace\Freeform\Fields\Properties\Options\Elements\Types\Users\Users;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Countries\Countries;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Currencies\Currencies;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Days\Days;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\DaysOfWeek\DaysOfWeek;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Languages\Languages;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Months\Months;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Numbers\Numbers;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Provinces\Provinces;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\States\States;
use Solspace\Freeform\Fields\Properties\Options\Predefined\Types\Years\Years;
use yii\base\Event;

class OptionTypesProvider
{
    public const EVENT_REGISTER_ELEMENT_TYPES = 'register-element-types';
    public const EVENT_REGISTER_PREDEFINED_TYPES = 'register-predefined-types';

    public function getElementTypes(): array
    {
        $types = [
            new Assets(),
            new Entries(),
            new Users(),
            new Categories(),
            new Tags(),
        ];

        $event = new RegisterOptionTypesEvent($types);
        Event::trigger(self::class, self::EVENT_REGISTER_ELEMENT_TYPES, $event);

        return $event->getTypes();
    }

    public function getPredefinedTypes(): array
    {
        $types = [
            new States(),
            new Provinces(),
            new Countries(),
            new Languages(),
            new Currencies(),
            new Numbers(),
            new Years(),
            new Months(),
            new Days(),
            new DaysOfWeek(),
        ];

        $event = new RegisterOptionTypesEvent($types);
        Event::trigger(self::class, self::EVENT_REGISTER_PREDEFINED_TYPES, $event);

        return $event->getTypes();
    }
}
