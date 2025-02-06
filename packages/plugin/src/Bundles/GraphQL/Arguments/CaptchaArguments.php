<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class CaptchaArguments extends Arguments
{
    public static function getArguments(): array
    {
        return [
            'version' => [
                'name' => 'version',
                'type' => Type::string(),
                'description' => 'The Captcha version',
            ],
            'triggerOnInteract' => [
                'name' => 'triggerOnInteract',
                'type' => Type::boolean(),
                'description' => 'Will the Captcha trigger on interaction',
            ],
            'failureBehavior' => [
                'name' => 'failureBehavior',
                'type' => Type::string(),
                'description' => 'The Captcha failure behavior',
            ],
            'errorMessage' => [
                'name' => 'errorMessage',
                'type' => Type::string(),
                'description' => 'The Captcha custom error message',
            ],
            'theme' => [
                'name' => 'theme',
                'type' => Type::string(),
                'description' => 'The Captcha theme',
            ],
            'size' => [
                'name' => 'size',
                'type' => Type::string(),
                'description' => 'The Captcha size',
            ],
            'scoreThreshold' => [
                'name' => 'scoreThreshold',
                'type' => Type::string(),
                'description' => 'The Captcha score threshold',
            ],
            'action' => [
                'name' => 'action',
                'type' => Type::string(),
                'description' => 'The Captcha action',
            ],
            'locale' => [
                'name' => 'locale',
                'type' => Type::string(),
                'description' => 'The Captcha locale',
            ],
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The Captcha field input name (E.g "g-recaptcha-response", "h-captcha-response" or "cf-turnstile-response")',
            ],

            /*
             * @deprecated - this argument is no longer used
             *
             * @remove - Freeform 6.0
             */
            'handle' => [
                'name' => 'handle',
                'type' => Type::string(),
                'description' => 'The Captcha field input name (E.g "g-recaptcha-response", "h-captcha-response" or "cf-turnstile-response"). Deprecated. Will be removed in Freeform 6.0.',
            ],

            /*
             * @deprecated - this argument is no longer used
             *
             * @remove - Freeform 6.0
             */
            'enabled' => [
                'name' => 'enabled',
                'type' => Type::boolean(),
                'description' => 'The Captcha state. Deprecated. Will be removed in Freeform 6.0.',
            ],
        ];
    }
}
