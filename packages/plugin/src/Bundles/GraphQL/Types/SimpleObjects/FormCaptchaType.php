<?php

// FIXME - Move out of SimpleObjects

namespace Solspace\Freeform\Bundles\GraphQL\Types\SimpleObjects;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Bundles\GraphQL\Interfaces\SimpleObjects\FormCaptchaInterface;
use Solspace\Freeform\Bundles\GraphQL\Types\AbstractObjectType;
use Solspace\Freeform\Fields\DataContainers\Option;

class FormCaptchaType extends AbstractObjectType
{
    public static function getName(): string
    {
        return 'FreeformFormCaptchaType';
    }

    public static function getTypeDefinition(): Type
    {
        return FormCaptchaInterface::getType();
    }

    /**
     * @param Option $source
     * @param mixed  $arguments
     *
     * FIXME
     * - Add proper captcha field handles
     * - Deprecate name, handle and enabled and remove in version 6
     */
    protected function resolve($source, $arguments, mixed $context, ResolveInfo $resolveInfo): mixed
    {
        if ('name' === $resolveInfo->fieldName) {
            return $source['name'] ?? null;
        }

        if ('handle' === $resolveInfo->fieldName) {
            return $source['handle'] ?? null;
        }

        if ('enabled' === $resolveInfo->fieldName) {
            return $source['enabled'] ?? null;
        }

        if ('fieldHandle' === $resolveInfo->fieldName) {
            return $source['fieldHandle'] ?? null;
        }

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

        return null;
    }
}
