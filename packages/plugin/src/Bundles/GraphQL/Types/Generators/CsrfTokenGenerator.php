<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\CsrfTokenArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\CsrfTokenInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\CsrfTokenType;

class CsrfTokenGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return CsrfTokenType::class;
    }

    public static function getArgumentsClass(): string
    {
        return CsrfTokenArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return CsrfTokenInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform CSRF Token entity';
    }
}
