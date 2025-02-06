<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use Solspace\Freeform\Events\Forms\DisableFunctionalityEvent;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\Providers\FormMonitorProvider;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class RequestRecognition extends FeatureBundle
{
    public function __construct(
        private FormMonitorProvider $provider,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_DISABLE_FUNCTIONALITY,
            [$this, 'handleFunctionality'],
        );

        Event::on(
            Form::class,
            Form::EVENT_SUBMIT,
            [$this, 'handleSubmit'],
        );
    }

    public function handleFunctionality(DisableFunctionalityEvent $event): void
    {
        $form = $event->getForm();
        $isRequestFromFormMonitor = $this->provider->isRequestFromFormMonitor($form);
        if (!$isRequestFromFormMonitor) {
            return;
        }

        $event->setSettings([
            'api' => true,
            'elements' => true,
            'payments' => true,
            'webhooks' => true,
            'captchas' => true,
            'honeypot' => true,
            'javascriptTest' => true,
        ]);
    }

    public function handleSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        $isRequestFromFormMonitor = $this->provider->isRequestFromFormMonitor($form);
        if (!$isRequestFromFormMonitor) {
            return;
        }

        $submission = $event->getSubmission();
        $submission->isHidden = true;
        $submission->requestId = $this->provider->getRequestId($form);
    }
}
