<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects;

use Solspace\Freeform\Bundles\GraphQL\Arguments\SubmissionCaptchaArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\AbstractInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects\SubmissionCaptchaGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\SubmissionCaptchaType;

class SubmissionCaptchaInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformSubmissionCaptchaInterface';
    }

    public static function getTypeClass(): string
    {
        return SubmissionCaptchaType::class;
    }

    public static function getGeneratorClass(): string
    {
        return SubmissionCaptchaGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Submission Captcha GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            SubmissionCaptchaArguments::getArguments(),
            static::getName(),
        );
    }
}
