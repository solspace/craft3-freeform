<?php

namespace Solspace\Freeform\Services;

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;

class FormMonitorService extends BaseService
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private IntegrationClientProvider $clientProvider,
    ) {}

    public function getStatus(Form $form): array
    {
        $integration = $this->integrationsProvider->getFirstForForm($form, FormMonitor::class);

        $isEnabled = $integration && $integration->isEnabled();

        if ($isEnabled) {
            try {
                $client = $this->clientProvider->getAuthorizedClient($integration);
                $stats = $integration->fetchStats($client, $form);

                return [
                    'enabled' => true,
                    'stats' => $stats,
                ];
            } catch (\Exception $e) {
                // If API call fails, return enabled but no stats
                return [
                    'enabled' => true,
                ];
            }
        }

        return [
            'enabled' => false,
        ];
    }
}
