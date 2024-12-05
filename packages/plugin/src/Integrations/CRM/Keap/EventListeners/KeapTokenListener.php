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

namespace Solspace\Freeform\Integrations\CRM\Keap\EventListeners;

use Solspace\Freeform\Events\Integrations\OAuth2\InitiateAuthenticationFlowEvent;
use Solspace\Freeform\Integrations\CRM\Keap\KeapIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use yii\base\Event;

class KeapTokenListener extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            OAuth2ConnectorInterface::class,
            OAuth2ConnectorInterface::EVENT_INITIATE_AUTHENTICATION_FLOW,
            [$this, 'onInitAuthentication']
        );
    }

    public function onInitAuthentication(InitiateAuthenticationFlowEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof KeapIntegrationInterface) {
            return;
        }

        $event->add('scope', 'full');
    }
}
