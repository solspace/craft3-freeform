<?php

namespace Solspace\Freeform\Bundles\GraphQL\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\CaptchaInterface;
use Solspace\Freeform\Fields\DataContainers\Option;

class CaptchaType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformCaptchaType';
    }

    public static function getTypeDefinition(): Type
    {
        return CaptchaInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('version' === $resolveInfo->fieldName) {
            return $source['version'] ?? null;
        }

        if ('triggerOnInteract' === $resolveInfo->fieldName) {
            return $source['triggerOnInteract'] ?? null;
        }

        if ('failureBehavior' === $resolveInfo->fieldName) {
            return $source['failureBehavior'] ?? null;
        }

        if ('errorMessage' === $resolveInfo->fieldName) {
            return $source['errorMessage'] ?? null;
        }

        if ('theme' === $resolveInfo->fieldName) {
            return $source['theme'] ?? null;
        }

        if ('size' === $resolveInfo->fieldName) {
            return $source['size'] ?? null;
        }

        if ('scoreThreshold' === $resolveInfo->fieldName) {
            return $source['scoreThreshold'] ?? null;
        }

        if ('action' === $resolveInfo->fieldName) {
            return $source['action'] ?? null;
        }

        if ('locale' === $resolveInfo->fieldName) {
            return $source['locale'] ?? null;
        }

        if ('name' === $resolveInfo->fieldName) {
            return $source['name'] ?? null;
        }

        /*
         * @deprecated - this argument is no longer used
         *
         * @remove - Freeform 6.0
         */
        if ('handle' === $resolveInfo->fieldName) {
            return $source['handle'] ?? null;
        }

        /*
         * @deprecated - this argument is no longer used
         *
         * @remove - Freeform 6.0
         */
        if ('enabled' === $resolveInfo->fieldName) {
            return $source['enabled'] ?? null;
        }

        return null;
    }
}
