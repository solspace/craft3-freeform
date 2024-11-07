<?php

namespace Solspace\Freeform\Integrations\Other\Jira\EventListeners;

use GuzzleHttp\Client;
use Solspace\Freeform\Events\Integrations\OAuth2\InitiateAuthenticationFlowEvent;
use Solspace\Freeform\Events\Integrations\OAuth2\TokenPayloadEvent;
use Solspace\Freeform\Integrations\Other\Jira\JiraIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use yii\base\Event;

class JiraTokenListener extends FeatureBundle
{
    public function __construct(
    ) {
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

    public static function getPriority(): int
    {
        return 2000;
    }

    public function onInitAuthentication(InitiateAuthenticationFlowEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof JiraIntegrationInterface) {
            return;
        }

        // Define the required scopes for Google Sheets and Drive
        $scopes = [
            'offline_access',
            'write:jira-work',
            'read:jira-work',
            'read:jira-user',
        ];

        // Join scopes with a space delimiter
        $formattedScopes = implode(' ', $scopes);

        // Add necessary parameters to the authentication event
        $event
            ->add('scope', $formattedScopes)
            ->add('prompt', 'consent')
            ->add('access_type', 'offline')
            ->add('audience', 'api.atlassian.com')
        ;
    }

    public function onAfterAuthorize(TokenPayloadEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof JiraIntegrationInterface) {
            return;
        }

        $payload = $event->getResponsePayload();

        $accessToken = $payload->access_token;
        $instanceUrl = $integration->getInstanceUrl();

        $client = new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
                'Accept' => 'application/json',
            ],
        ]);

        $response = $client->get('https://api.atlassian.com/oauth/token/accessible-resources');
        $resources = json_decode((string) $response->getBody());

        foreach ($resources as $resource) {
            if (!str_contains($resource->url, $instanceUrl) && !empty($instanceUrl)) {
                continue;
            }

            $integration->setInstanceUrl($resource->url);
            $integration->setCloudId($resource->id);

            break;
        }

        if (!$integration->getInstanceUrl()) {
            throw new IntegrationException('Could not find the instance URL in the accessible resources');
        }

        $integration->populateParameters($client);
    }
}
