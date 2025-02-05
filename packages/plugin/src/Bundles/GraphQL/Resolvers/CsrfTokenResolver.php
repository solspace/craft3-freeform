<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Form\Form;

class CsrfTokenResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        if (!$source instanceof Form) {
            return null;
        }

        $generalConfig = \Craft::$app->getConfig()->getGeneral();
        $isCsrfEnabled = $generalConfig->enableCsrfProtection;
        $csrfTokenName = $generalConfig->csrfTokenName;
        $csrfTokenValue = \Craft::$app->getRequest()->getCsrfToken();

        if (!$isCsrfEnabled || !$csrfTokenName || !$csrfTokenValue) {
            return null;
        }

        return [
            'name' => $csrfTokenName,
            'value' => $csrfTokenValue,
        ];
    }
}
