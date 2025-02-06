<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments\Inputs;

use craft\gql\base\Arguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Inputs\DynamicNotificationInputType;

class DynamicNotificationInputArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'dynamicNotification' => [
                'name' => 'dynamicNotification',
                'type' => DynamicNotificationInputType::getType(),
                'description' => 'Allows using a dynamic template level notification for a more fine-grained control.',
            ],
        ];
    }
}
