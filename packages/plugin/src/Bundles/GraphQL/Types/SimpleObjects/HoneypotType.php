<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\HoneypotInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\AbstractObjectType;
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
     *
     * FIXME
     *  - Add inputName
     *  - Deprecate name and value and remove in version 6
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('errorMessage' === $resolveInfo->fieldName) {
            return $source['errorMessage'] ?? null;
        }

        if ('name' === $resolveInfo->fieldName) {
            return $source['name'] ?? null;
        }

        if ('value' === $resolveInfo->fieldName) {
            return $source['value'] ?? null;
        }

        return null;
    }
}
