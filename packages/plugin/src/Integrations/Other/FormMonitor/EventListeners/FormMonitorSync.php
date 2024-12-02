<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use Solspace\Freeform\Events\Integrations\SaveEvent;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use yii\base\Event;

class FormMonitorSync extends FeatureBundle
{
    public function __construct(
        private IntegrationsService $integrationsService,
    ) {
        Event::on(
            FormsService::class,
            FormsService::EVENT_AFTER_SAVE,
            [$this, 'handleSync']
        );
    }

    public function handleSync(SaveEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof FormMonitor) {
            return;
        }
    }
}
