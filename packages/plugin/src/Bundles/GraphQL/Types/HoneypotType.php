<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\HoneypotInterface;
use Solspace\Freeform\Fields\DataContainers\Option;

class HoneypotType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformHoneypotType';
    }

    public static function getTypeDefinition(): Type
    {
        return HoneypotInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('errorMessage' === $resolveInfo->fieldName) {
            return $source['errorMessage'] ?? null;
        }

        if ('name' === $resolveInfo->fieldName) {
            return $source['name'] ?? null;
        }

        /*
         * @deprecated - this argument is no longer used
         *
         * @remove - Freeform 6.0
         */
        if ('value' === $resolveInfo->fieldName) {
            return $source['value'] ?? null;
        }

        return null;
    }
}
