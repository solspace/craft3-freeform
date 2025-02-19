<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Services\LoggerService;
use yii\base\Event;
use yii\web\BadRequestHttpException;

class RemoveIntegrationListener extends FeatureBundle
{
    public function __construct(
        private IntegrationClientProvider $clientProvider,
        private LoggerService $loggerService,
    ) {
        Event::on(
            FormsController::class,
            FormsController::EVENT_UPDATE_FORM,
            [$this, 'handleIntegrationChange'],
        );
    }

    public static function getPriority(): int
    {
        return 300;
    }

    public function handleIntegrationChange(PersistFormEvent $event): void
    {
        $payload = $event->getPayload();
        $form = $event->getForm();
        $integrations = $payload->integrations ?? [];
        foreach ($integrations as $integrationData) {
            if ($integrationData->enabled) {
                return;
            }
            $integrationId = $integrationData->id;
            $integration = Freeform::getInstance()->integrations->getIntegrationObjectById($integrationId);
            if ($integration instanceof FormMonitor) {
                $record = FormIntegrationRecord::find()
                    ->where([
                        'formId' => $form->getId(),
                        'integrationId' => $integrationId,
                        'enabled' => true,
                    ])
                    ->one()
                ;

                if ($record) {
                    $client = $this->clientProvider->getAuthorizedClient($integration);

                    try {
                        $integration->deleteManifest($client, $form);
                    } catch (BadRequestHttpException $exception) {
                        $this->loggerService
                            ->getLogger('Form Monitor')
                            ->error($exception->getMessage())
                        ;
                    }
                }
            }
        }
    }
}
