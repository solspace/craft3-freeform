<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\CaptchaArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\CaptchaType;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\CaptchaGenerator;

class CaptchaInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformCaptchaInterface';
    }

    public static function getTypeClass(): string
    {
        return CaptchaType::class;
    }

    public static function getGeneratorClass(): string
    {
        return CaptchaGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Captcha GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            CaptchaArguments::getArguments(),
            static::getName(),
        );
    }
}
