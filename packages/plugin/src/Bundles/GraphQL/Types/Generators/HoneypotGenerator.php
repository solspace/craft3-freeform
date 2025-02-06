<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\HoneypotArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\HoneypotInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\HoneypotType;

class HoneypotGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return HoneypotType::class;
    }

    public static function getArgumentsClass(): string
    {
        return HoneypotArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return HoneypotInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Honeypot entity';
    }
}
