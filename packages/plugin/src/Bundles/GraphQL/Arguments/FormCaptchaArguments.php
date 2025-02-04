<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class FormCaptchaArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA field handle',
            ],
            'version' => [
                'name' => 'version',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA version',
            ],
            'triggerOnInteract' => [
                'name' => 'triggerOnInteract',
                'type' => Type::boolean(),
                'description' => 'Will the reCAPTCHA trigger on interaction',
            ],
            'failureBehavior' => [
                'name' => 'failureBehavior',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA failure behavior',
            ],
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA custom error message',
            ],
            'theme' => [
                'name' => 'theme',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA theme',
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA size',
            ],
            'scoreThreshold' => [
                'name' => 'scoreThreshold',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA score threshold',
            ],
            'action' => [
                'name' => 'action',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA action',
            ],
            'locale' => [
                'name' => 'locale',
                'type' => Type::string(),
                'description' => 'The reCAPTCHA locale',
            ],
        ];
    }
}
