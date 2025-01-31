<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\GoogleTagManagerArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\GoogleTagManagerGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\GoogleTagManagerType;

class GoogleTagManagerInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformGoogleTagManagerInterface';
    }

    public static function getTypeClass(): string
    {
        return GoogleTagManagerType::class;
    }

    public static function getGeneratorClass(): string
    {
        return GoogleTagManagerGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Google Tag Manager GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            GoogleTagManagerArguments::getArguments(),
            static::getName(),
        );
    }
}
