<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Inputs;

use craft\gql\GqlEntityRegistry;
use GraphQL\Type\Definition\InputObjectType;
use Solspace\Freeform\Bundles\GraphQL\Arguments\SubmissionCaptchaArguments;

class SubmissionCaptchaInputType extends InputObjectType
{
    public static function getName(): string
    {
        return 'FreeformSubmissionCaptchaInputType';
    }

    public static function getType(): mixed
    {
        if ($inputType = GqlEntityRegistry::getEntity(self::getName())) {
            return $inputType;
        }

        /*
         * FIXME
         * - Add g-recaptcha-response, h-captcha-response or cf-turnstile-response field input names instead of requiring name / value fields.
         * - Also add proper SubmissionCaptchaInputArguments so we do not expose query fields for mutations
         */
        $fields = \Craft::$app->getGql()->prepareFieldDefinitions(
            SubmissionCaptchaArguments::getArguments(),
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
