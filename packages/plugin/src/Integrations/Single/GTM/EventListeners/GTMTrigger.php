<?php

namespace Solspace\Freeform\Integrations\Single\GTM\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Single\GTM\GTM;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class GTMTrigger extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_RENDER_AFTER_CLOSING_TAG,
            [$this, 'attachScript']
        );

        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this, 'attachToJson']
        );
    }

    public function attachScript(RenderTagEvent $event): void
    {
        $form = $event->getForm();

        $integration = $this->integrationsProvider->getSingleton($form, GTM::class);
        if (!$integration) {
            return;
        }

        $eventName = $integration->getEventName() ?: 'form-submission';

        $gtmTriggerScript = file_get_contents(__DIR__.'/../Scripts/gtm-trigger.js');
        $event->addChunk("<script>{$gtmTriggerScript}</script>", [
            'form' => $form,
            'eventName' => $eventName,
        ]);

        if (!$integration->getContainerId()) {
            return;
        }

        $containerId = $integration->getContainerId();

        $gtmImportScript = file_get_contents(__DIR__.'/../Scripts/gtm-import.js');
        $event->addChunk(
            "<!-- Google Tag Manager --><script>{$gtmImportScript}</script><!-- End Google Tag Manager -->",
            ['containerId' => $containerId],
        );

        $event->addChunk('<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id='.$containerId.'"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->');
    }

    public function attachToJson(OutputAsJsonEvent $event): void
    {
        $form = $event->getForm();
        $integration = $this->integrationsProvider->getSingleton($form, GTM::class);
        if (!$integration) {
            return;
        }

        $event->add('gtm', [
            'containerId' => $integration->getContainerId(),
            'eventName' => $integration->getEventName(),
        ]);
    }
}
