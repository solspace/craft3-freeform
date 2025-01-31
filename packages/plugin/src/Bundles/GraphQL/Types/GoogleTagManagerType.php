<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\GoogleTagManagerInterface;
use Solspace\Freeform\Fields\DataContainers\Option;

class GoogleTagManagerType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformGoogleTagManagerType';
    }

    public static function getTypeDefinition(): Type
    {
        return GoogleTagManagerInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('containerId' === $resolveInfo->fieldName) {
            return $source['containerId'] ?? null;
        }

        if ('eventName' === $resolveInfo->fieldName) {
            return $source['eventName'] ?? null;
        }

        return null;
    }
}
