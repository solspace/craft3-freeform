<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\CsrfTokenInterface;
use Solspace\Freeform\Fields\DataContainers\Option;

class CsrfTokenType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformCsrfTokenType';
    }

    public static function getTypeDefinition(): Type
    {
        return CsrfTokenInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('name' === $resolveInfo->fieldName) {
            return $source['name'] ?? null;
        }

        if ('value' === $resolveInfo->fieldName) {
            return $source['value'] ?? null;
        }

        return null;
    }
}
