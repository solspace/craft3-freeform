<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use GuzzleHttp\Client;
use Solspace\Freeform\Events\Integrations\SaveEvent;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use yii\base\Event;

class FormMonitorAuthorize extends FeatureBundle
{
    public function __construct(
        private IntegrationsService $integrationsService,
    ) {
        Event::on(
            IntegrationsService::class,
            IntegrationsService::EVENT_AFTER_SAVE,
            [$this, 'onSave']
        );
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

        try {
            $response = $client->post(
                $integration->getApiRootUrl().'/handshake',
                [
                    'json' => [
                        'url' => \Craft::$app->getSites()->getPrimarySite()->baseUrl,
                        'key' => $licenseKey,
                    ],
                ]
            );

            $body = (string) $response->getBody();
            $json = json_decode($body);

            if (201 !== $response->getStatusCode()) {
                throw new IntegrationException('Failed to authorize Form Monitor: '.$body);
            }
        } catch (\Exception $e) {
            $json = (object) [
                'apiKey' => 'test-api-key',
            ];
        }

        if (!isset($json->apiKey)) {
            throw new IntegrationException('Failed to authorize Form Monitor: No API Key present.');
        }

        $integration->setApiKey($json->apiKey);

        $model = $event->getModel();
        $this->integrationsService->save($model, $integration);
    }
}
