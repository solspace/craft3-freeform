<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Integrations\CRM\HubSpot\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\Events\Integrations\GetAuthorizedClientEvent;
use Solspace\Freeform\Integrations\CRM\HubSpot\HubSpotIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class HubSpotClientConfiguration extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            IntegrationClientProvider::class,
            IntegrationClientProvider::EVENT_GET_CLIENT,
            [$this, 'configureClient']
        );
    }

    public function configureClient(GetAuthorizedClientEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof HubSpotIntegrationInterface) {
            return;
        }

        $event->addConfig(
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$integration->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
            ],
        );
    }
}
