<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use Solspace\Freeform\Bundles\GraphQL\Arguments\Inputs\DynamicNotificationInputArguments;

class FormPropertiesInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformFormPropertiesInputType';
    }

    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            array_merge(
                DynamicNotificationInputArguments::getArguments(),
            ),
            self::getName()
        );

        return GqlEntityRegistry::createEntity(self::getName(), new self([
            'name' => self::getName(),
            'fields' => function () use ($fields) {
                return $fields;
            },
        ]));
    }
}
