<?php

namespace Solspace\Freeform\Bundles\Integrations\Providers;

use GuzzleHttp\Client;
use Solspace\Freeform\Events\Integrations\GetAuthorizedClientEvent;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use yii\base\Event;

class IntegrationClientProvider
{
    public const EVENT_GET_CLIENT = 'get-client';

    public function getAuthorizedClient(IntegrationInterface $integration): Client
    {
        $event = new GetAuthorizedClientEvent($integration);
        Event::trigger(
            self::class,
            self::EVENT_GET_CLIENT,
            $event,
        );

        $config = $event->getConfig();
        if ($event->getStack()) {
            $config['handler'] = $event->getStack();
        }

        return \Craft::createGuzzleClient($config);
    }
}
