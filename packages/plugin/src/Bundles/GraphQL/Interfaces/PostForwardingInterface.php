<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\PostForwardingArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\PostForwardingGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\PostForwardingType;

class PostForwardingInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformPostForwardingInterface';
    }

    public static function getTypeClass(): string
    {
        return PostForwardingType::class;
    }

    public static function getGeneratorClass(): string
    {
        return PostForwardingGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Post Forwarding GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            PostForwardingArguments::getArguments(),
            static::getName(),
        );
    }
}
