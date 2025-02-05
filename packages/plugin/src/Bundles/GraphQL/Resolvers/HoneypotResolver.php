<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\Honeypot\Honeypot;

class HoneypotResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        if (!$source instanceof Form) {
            return null;
        }

        $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
        $honeypot = $integrationProvider->getSingleton($source, Honeypot::class);
        if (!$honeypot) {
            return null;
        }

        return [
            'errorMessage' => $honeypot->getErrorMessage(),
            'name' => $honeypot->getInputName(),

            /*
             * @deprecated - this argument is no longer used
             *
             * @remove - Freeform 6.0
             */
            'value' => '',
        ];
    }
}
