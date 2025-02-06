<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\CaptchaArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\CaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\CaptchaType;

class CaptchaGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return CaptchaType::class;
    }

    public static function getArgumentsClass(): string
    {
        return CaptchaArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return CaptchaInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Captcha entity';
    }
}
