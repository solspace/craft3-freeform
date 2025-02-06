<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\HoneypotArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\HoneypotGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\HoneypotType;

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
