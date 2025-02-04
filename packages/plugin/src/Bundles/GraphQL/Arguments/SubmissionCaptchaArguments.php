<?php

namespace Solspace\Freeform\Bundles\GraphQL\Arguments;

use craft\gql\base\Arguments;
use GraphQL\Type\Definition\Type;

class SubmissionCaptchaArguments extends Arguments
{
    public static function getArguments(): array
    {
        /*
         * FIXME
         * - Add proper captcha type field handles
         * - deprecate name and value and remove in version 6
         */
        return [
            'name' => [
                'name' => 'name',
                'type' => Type::string(),
                'description' => 'The Captcha field name (E.g "g-recaptcha-response", "h-captcha-response" or "cf-turnstile-response").',
            ],
            'value' => [
                'name' => 'value',
                'type' => Type::string(),
                'description' => 'The Captcha verification response value.',
            ],
            'fieldHandle' => [
                'name' => 'fieldHandle',
                'type' => Type::string(),
                'description' => 'The Captcha field handle',
            ],
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
        ];
    }
}
