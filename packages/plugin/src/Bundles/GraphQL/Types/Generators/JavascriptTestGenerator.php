<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\JavascriptTestArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\JavascriptTestInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\JavascriptTestType;

class JavascriptTestGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return JavascriptTestType::class;
    }

    public static function getArgumentsClass(): string
    {
        return JavascriptTestArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return JavascriptTestInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Javascript Test entity';
    }
}
