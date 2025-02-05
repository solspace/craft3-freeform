<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class JavascriptTestArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The Javascript Test custom error message.',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The Javascript Test field input name.',
            ],
        ];
    }
}
