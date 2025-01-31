<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\GTM\GTM;

class GoogleTagManagerResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        if (!$source instanceof Form) {
            return null;
        }

        $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
        $gtm = $integrationProvider->getSingleton($source, GTM::class);
        if (!$gtm) {
            return null;
        }

        return [
            'containerId' => $gtm->getContainerId(),
            'eventName' => $gtm->getEventName(),
        ];
    }
}
