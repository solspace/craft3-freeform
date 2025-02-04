<?php

/*
 * FIXME
 * - Deprecate captcha resolver and remove in version 6
 * - Add proper reCaptcha, hCaptcha and turnstiles resolvers
 * - Move out of SimpleObjects
 */

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects;

use Solspace\Freeform\Bundles\GraphQL\Arguments\SubmissionCaptchaArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\SubmissionCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\AbstractGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\SubmissionCaptchaType;

class SubmissionCaptchaGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return SubmissionCaptchaType::class;
    }

    public static function getArgumentsClass(): string
    {
        return SubmissionCaptchaArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return SubmissionCaptchaInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Submission Captcha entity';
    }
}
