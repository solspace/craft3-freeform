<?php

namespace Solspace\Freeform\Bundles\GraphQL\Interfaces;

use Solspace\Freeform\Bundles\GraphQL\Arguments\JavascriptTestArguments;
use Solspace\Freeform\Bundles\GraphQL\Types\Generators\JavascriptTestGenerator;
use Solspace\Freeform\Bundles\GraphQL\Types\JavascriptTestType;

class JavascriptTestInterface extends AbstractInterface
{
    public static function getName(): string
    {
        return 'FreeformJavascriptTestInterface';
    }

    public static function getTypeClass(): string
    {
        return JavascriptTestType::class;
    }

    public static function getGeneratorClass(): string
    {
        return JavascriptTestGenerator::class;
    }

    public static function getDescription(): string
    {
        return 'Freeform Javascript Test GraphQL Interface';
    }

    public static function getFieldDefinitions(): array
    {
        return \Craft::$app->gql->prepareFieldDefinitions(
            JavascriptTestArguments::getArguments(),
            static::getName(),
        );
    }
}
