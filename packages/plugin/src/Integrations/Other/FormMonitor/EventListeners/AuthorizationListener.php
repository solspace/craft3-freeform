<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use GuzzleHttp\Client;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\Events\Integrations\GetAuthorizedClientEvent;
use Solspace\Freeform\Events\Integrations\SaveEvent;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use yii\base\Event;

class AuthorizationListener extends FeatureBundle
{
    public function __construct(
        private IntegrationsService $integrationsService,
    ) {
        Event::on(
            IntegrationClientProvider::class,
            IntegrationClientProvider::EVENT_GET_CLIENT,
            [$this, 'onGetClient']
        );

        Event::on(
            IntegrationsService::class,
            IntegrationsService::EVENT_AFTER_SAVE,
            [$this, 'onSave']
        );
    }

    public function onGetClient(GetAuthorizedClientEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof FormMonitor) {
            return;
        }

        $event->addConfig([
            'headers' => [
                'Authorization' => 'Token '.$integration->getApiKey(),
            ],
        ]);
    }

    public function onSave(SaveEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof FormMonitor) {
            return;
        }

        $plugin = \Craft::$app->plugins->getPlugin('freeform');
        $licenseKey = \Craft::$app->plugins->getPluginLicenseKey($plugin->id);

        $client = new Client();

        $response = $client->post(
            $integration->getApiRootUrl().'/handshake',
            [
                'json' => [
                    'url' => \Craft::$app->getSites()->getPrimarySite()->baseUrl,
                    'email' => $integration->getEmail(),
                    'key' => $licenseKey,
                ],
            ]
        );

        $body = (string) $response->getBody();
        $json = json_decode($body);

        if (201 !== $response->getStatusCode()) {
            throw new IntegrationException('Failed to authorize Form Monitor: '.$body);
        }

        if (!isset($json->apiKey)) {
            throw new IntegrationException('Failed to authorize Form Monitor: No API Key present.');
        }

        if (!isset($json->requestToken)) {
            throw new IntegrationException('Failed to authorize Form Monitor: No Request Token present.');
        }

        $integration->setApiKey($json->apiKey);
        $integration->setRequestToken($json->requestToken);

        $model = $event->getModel();
        $this->integrationsService->save($model, $integration);
    }
}
