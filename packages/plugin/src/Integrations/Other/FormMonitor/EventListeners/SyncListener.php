<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Integrations\Other\FormMonitor\Transformers\ManifestTransformer;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\LoggerService;
use yii\base\Event;
use yii\web\BadRequestHttpException;

class SyncListener extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private IntegrationClientProvider $clientProvider,
        private ManifestTransformer $manifestTransformer,
        private LoggerService $loggerService,
    ) {
        Event::on(
            FormsController::class,
            FormsController::EVENT_AFTER_SAVE_FORM,
            [$this, 'handleSync']
        );
    }

    public function handleSync(PersistFormEvent $event): void
    {
        $form = $event->getForm();
        $formMonitor = $this->integrationsProvider->getFirstForForm($form, FormMonitor::class);
        if (!$formMonitor) {
            return;
        }

        $client = $this->clientProvider->getAuthorizedClient($formMonitor);

        try {
            $formMonitor->sendManifest($client, $form, $this->manifestTransformer);
        } catch (BadRequestHttpException $exception) {
            $this->loggerService
                ->getLogger('Form Monitor')
                ->error($exception->getMessage())
            ;
        }
    }
}
