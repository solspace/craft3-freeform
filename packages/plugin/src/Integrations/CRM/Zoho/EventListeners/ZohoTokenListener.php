<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Integrations\CRM\Zoho\EventListeners;

use Solspace\Freeform\Events\Integrations\OAuth2\FetchTokenEvent;
use Solspace\Freeform\Events\Integrations\OAuth2\InitiateAuthenticationFlowEvent;
use Solspace\Freeform\Events\Integrations\OAuth2\TokenPayloadEvent;
use Solspace\Freeform\Integrations\CRM\Zoho\ZohoIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\CRMIntegrationNotFoundException;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use yii\base\Event;

class ZohoTokenListener extends FeatureBundle
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
            OAuth2ConnectorInterface::EVENT_BEFORE_AUTHORIZE,
            [$this, 'onBeforeAuthorize']
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
        if (!$integration instanceof ZohoIntegrationInterface) {
            return;
        }

        $event
            ->add('scope', 'ZohoCRM.modules.READ,ZohoCRM.modules.CREATE,ZohoCRM.modules.ALL,ZohoCRM.settings.all')
            ->add('access_type', 'offline')
        ;
    }

    public function onBeforeAuthorize(FetchTokenEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof ZohoIntegrationInterface) {
            return;
        }

        $integration->setAccountsServer($_GET['accounts-server'] ?? '');
        $integration->setLocation($_GET['location'] ?? '');
    }

    public function onAfterAuthorize(TokenPayloadEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof ZohoIntegrationInterface) {
            return;
        }

        $payload = $event->getResponsePayload();
        if (!isset($payload->api_domain)) {
            throw new CRMIntegrationNotFoundException("Zoho response data doesn't contain the API Domain");
        }

        $integration->setApiDomain($payload->api_domain);
    }
}
