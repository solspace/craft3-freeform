<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationClientProvider;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\Providers\FormMonitorProvider;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\LoggerService;
use yii\base\Event;

class SubmissionAcknowledgement extends FeatureBundle
{
    public function __construct(
        private FormMonitorProvider $formMonitorProvider,
        private IntegrationClientProvider $clientProvider,
        private LoggerService $loggerService,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_AFTER_SUBMIT,
            [$this, 'acknowledge']
        );
    }

    public function acknowledge(SubmitEvent $event): void
    {
        if (!$this->formMonitorProvider->isFormMonitorEnabled()) {
            return;
        }

        $form = $event->getForm();

        $isFormMonitorRequest = $this->formMonitorProvider->isRequestFromFormMonitor($form);
        if (!$isFormMonitorRequest) {
            return;
        }

        $formMonitor = $this->formMonitorProvider->getFormMonitor($form);
        if (!$formMonitor) {
            return;
        }

        $submission = $event->getSubmission();
        $requestId = $this->formMonitorProvider->getRequestId($form);

        $client = $this->clientProvider->getAuthorizedClient($formMonitor);

        try {
            $formMonitor->acknowledgeSubmission($client, $form, $submission, $requestId);
        } catch (\Exception $e) {
            $this
                ->loggerService
                ->getLogger('Form Monitor')
                ->error($e->getMessage())
            ;
        }
    }
}
