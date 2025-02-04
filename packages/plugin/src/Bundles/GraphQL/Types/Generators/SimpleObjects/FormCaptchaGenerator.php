<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects;

use Solspace\Freeform\Bundles\GraphQL\Arguments\FormCaptchaArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\FormCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\AbstractGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\FormCaptchaType;

class FormCaptchaGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return FormCaptchaType::class;
    }

    public static function getArgumentsClass(): string
    {
        return FormCaptchaArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return FormCaptchaInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Form Captcha entity';
    }
}
