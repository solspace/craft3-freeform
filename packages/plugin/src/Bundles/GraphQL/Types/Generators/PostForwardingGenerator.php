<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types\Generators;

use Solspace\Freeform\Bundles\GraphQL\Arguments\PostForwardingArguments;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\PostForwardingInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\PostForwardingType;

class PostForwardingGenerator extends AbstractGenerator
{
    public static function getTypeClass(): string
    {
        return PostForwardingType::class;
    }

    public static function getArgumentsClass(): string
    {
        return PostForwardingArguments::class;
    }

    public static function getInterfaceClass(): string
    {
        return PostForwardingInterface::class;
    }

    public static function getDescription(): string
    {
        return 'The Freeform Post Forwarding entity';
    }
}
