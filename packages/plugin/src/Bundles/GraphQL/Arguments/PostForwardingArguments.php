<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class PostForwardingArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'url' => [
                'name' => 'url',
                'type' => Type::string(),
                'description' => 'The Post Forwarding URL.',
            ],
            'errorTrigger' => [
                'name' => 'errorTrigger',
                'type' => Type::string(),
                'description' => 'The Post Forwarding error trigger.',
            ],
            'sendFiles' => [
                'name' => 'sendFiles',
                'type' => Type::boolean(),
                'description' => 'Will Post Forwarding send files.',
            ],
        ];
    }
}
