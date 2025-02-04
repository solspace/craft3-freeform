<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects;

use Solspace\Freeform\Bundles\GraphQL\Arguments\HoneypotArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\AbstractInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\SimpleObjects\HoneypotGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects\HoneypotType;

class HoneypotInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformHoneypotInterface';
    }

    public static function getTypeClass(): string
    {
        return HoneypotType::class;
    }

    public static function getGeneratorClass(): string
    {
        return HoneypotGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Honeypot GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            HoneypotArguments::getArguments(),
            static::getName(),
        );
    }
}
