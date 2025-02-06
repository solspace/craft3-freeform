<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\CsrfTokenArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\CsrfTokenType;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\CsrfTokenGenerator;

class CsrfTokenInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformCsrfTokenInterface';
    }

    public static function getTypeClass(): string
    {
        return CsrfTokenType::class;
    }

    public static function getGeneratorClass(): string
    {
        return CsrfTokenGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform CSRF Token GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            CsrfTokenArguments::getArguments(),
            static::getName(),
        );
    }
}
