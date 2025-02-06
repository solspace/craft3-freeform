<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class GoogleTagManagerArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'containerId' => [
                'name' => 'containerId',
                'type' => Type::string(),
                'description' => 'The Google Tag Manager container ID',
            ],
            'eventName' => [
                'name' => 'eventName',
                'type' => Type::string(),
                'description' => 'The Google Tag Manager event name',
            ],
        ];
    }
}
