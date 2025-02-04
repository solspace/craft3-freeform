<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use Solspace\Freeform\Bundles\GraphQL\Arguments\DynamicNotificationArguments;

class DynamicNotificationInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformDynamicNotificationInputType';
    }

    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        // FIXME - Use proper DynamicNotificationInputArguments so we do not expose query fields for mutations
        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            DynamicNotificationArguments::getArguments(),
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
