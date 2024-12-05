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

namespace Solspace\Freeform\Integrations\CRM\Salesforce\EventListeners;

use Solspace\Freeform\Events\Integrations\OAuth2\InitiateAuthenticationFlowEvent;
use Solspace\Freeform\Events\Integrations\OAuth2\TokenPayloadEvent;
use Solspace\Freeform\Integrations\CRM\Salesforce\SalesforceIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\CRMIntegrationNotFoundException;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use yii\base\Event;

class SalesforceTokenListener extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            OAuth2ConnectorInterface::class,
            OAuth2ConnectorInterface::EVENT_INITIATE_AUTHENTICATION_FLOW,
            [$this, 'onInitAuthentication']
        );

        Event::on(
            OAuth2ConnectorInterface::class,
            OAuth2ConnectorInterface::EVENT_AFTER_AUTHORIZE,
            [$this, 'onAfterAuthorize']
        );
    }

    public function onInitAuthentication(InitiateAuthenticationFlowEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof SalesforceIntegrationInterface) {
            return;
        }

        $event->add('scope', 'refresh_token api');
    }

    public function onAfterAuthorize(TokenPayloadEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof SalesforceIntegrationInterface) {
            return;
        }

        $payload = $event->getResponsePayload();

        if (!isset($payload->instance_url)) {
            throw new CRMIntegrationNotFoundException("Salesforce response data doesn't contain the instance URL");
        }

        $integration->setInstanceUrl($payload->instance_url);
    }
}
