<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class HoneypotArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The Honeypot custom error message.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The Honeypot field input name.',
            ],

            /*
             * @deprecated - this argument is no longer used
             *
             * @remove - Freeform 6.0
             */
            'value' => [
                'name' => 'value',
                'type' => Type::string(),
                'description' => 'The Honeypot field input value. Deprecated. Will be removed in Freeform 6.0.',
            ],
        ];
    }
}
