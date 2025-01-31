<?php

namespace Solspace\Freeform\Bundles\GraphQL\Resolvers;

use craft\gql\base\Resolver;
use GraphQL\Type\Definition\ResolveInfo;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\PostForwarding\PostForwarding;

class PostForwardingResolver extends Resolver
{
    public static function resolve($source, array $arguments, $context, ResolveInfo $resolveInfo): ?array
    {
        if (!$source instanceof Form) {
            return null;
        }

        $integrationProvider = \Craft::$container->get(FormIntegrationsProvider::class);
        $postForwarding = $integrationProvider->getSingleton($source, PostForwarding::class);
        if (!$postForwarding) {
            return null;
        }

        return [
            'url' => $postForwarding->getUrl(),
            'errorTrigger' => $postForwarding->getErrorTrigger(),
            'sendFiles' => $postForwarding->isSendFiles(),
        ];
    }
}
