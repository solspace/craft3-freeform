<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

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
            Form::EVENT_SUBMIT,
            [$this, 'handleSubmit'],
        );
    }

    public function handleSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        $isRequestFromFormMonitor = $this->provider->isRequestFromFormMonitor($form);
        if (!$isRequestFromFormMonitor) {
            return;
        }

        $event->isValid = false;
    }
}
