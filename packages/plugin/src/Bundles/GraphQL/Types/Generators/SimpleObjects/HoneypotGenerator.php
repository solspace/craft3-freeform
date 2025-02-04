<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects;

use Solspace\Freeform\Bundles\GraphQL\Arguments\HoneypotArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\HoneypotInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\AbstractGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\HoneypotType;

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
