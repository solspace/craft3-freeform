<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\PostForwardingInterface;
use Solspace\Freeform\Fields\DataContainers\Option;

class PostForwardingType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformPostForwardingType';
    }

    public static function getTypeDefinition(): Type
    {
        return PostForwardingInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('url' === $resolveInfo->fieldName) {
            return $source['url'] ?? null;
        }

        if ('errorTrigger' === $resolveInfo->fieldName) {
            return $source['errorTrigger'] ?? null;
        }

        if ('sendFiles' === $resolveInfo->fieldName) {
            return $source['sendFiles'] ?? null;
        }

        return null;
    }
}
