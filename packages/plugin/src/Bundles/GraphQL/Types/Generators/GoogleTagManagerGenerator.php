<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\GoogleTagManagerArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\GoogleTagManagerInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\GoogleTagManagerType;

class GoogleTagManagerGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return GoogleTagManagerType::class;
    }

    public static function getArgumentsClass(): string
    {
        return GoogleTagManagerArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return GoogleTagManagerInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Google Tag Manager entity';
    }
}
