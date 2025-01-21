<?php

namespace Solspace\Freeform\Integrations\Single\JavascriptTest\EventListeners;

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\PrepareAjaxResponsePayloadEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Single\JavascriptTest\JavascriptTest;
use Solspace\Freeform\Library\Attributes\Attributes;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\DataObjects\SpamReason;
use Solspace\Freeform\Library\Helpers\CryptoHelper;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Event;

class JavascriptTestBundle extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private IntegrationLoggerProvider $loggerProvider,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this, 'addJsTestToJson']
        );

        Event::on(
            Form::class,
            Form::EVENT_RENDER_AFTER_OPEN_TAG,
            [$this, 'addJsTestInputToForm']
        );

        Event::on(
            Form::class,
            Form::EVENT_RENDER_AFTER_CLOSING_TAG,
            [$this, 'addJsTestScript']
        );

        Event::on(
            Form::class,
            Form::EVENT_COLLECT_SCRIPTS,
            [$this, 'collectScripts']
        );

        Event::on(
            Form::class,
            Form::EVENT_BEFORE_VALIDATE,
            [$this, 'validateJavascript']
        );

        Event::on(
            Form::class,
            Form::EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD,
            [$this, 'attachToAjaxPayload']
        );
    }

    public function addJsTestInputToForm(RenderTagEvent $event): void
    {
        $form = $event->getForm();
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return;
        }

        $event->addChunk($this->getJsTestInput($form));
    }

    public function addJsTestToJson(OutputAsJsonEvent $event): void
    {
        $form = $event->getForm();
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return;
        }

        $event->add('jsTest', ['name' => $integration->getInputName()]);
    }

    public function validateJavascript(ValidationEvent $event): void
    {
        $form = $event->getForm();
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return;
        }

        $logger = $this->loggerProvider->getLogger($integration);

        $jsTestInputName = $integration->getInputName();
        $settings = $this->getSettingsService();

        $settingsModel = $settings->getSettingsModel();
        if ($settingsModel->bypassSpamCheckOnLoggedInUsers && \Craft::$app->getUser()->id) {
            $logger->debug('Skipping Javascript Test check for logged in user');

            return;
        }

        if ($form->isGraphQLPosted()) {
            $logger->debug('Skipping Javascript Test check for GraphQL request');

            return;
        }

        /** @var array $postValues */
        $postedValue = \Craft::$app->request->post($jsTestInputName);
        if ('' === $postedValue) {
            $logger->debug('Javascript Test passed successfully.');

            return;
        }

        if ($settings->isSpamBehaviorDisplayErrors()) {
            $errorMessage = $integration->getErrorMessage();
            if (!$errorMessage) {
                $errorMessage = 'Javascript Test is invalid';
            }

            $form->addError(Freeform::t($errorMessage));
        }

        $form->markAsSpam(SpamReason::TYPE_JS_TEST, 'Javascript Test failed');
        $logger->debug('Javascript Test failed.');
    }

    public function getJsTestInput(Form $form): string
    {
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return '';
        }

        $name = $integration->getInputName();
        $fieldPrefix = $form->getFieldPrefix();
        $id = $fieldPrefix.$name;

        $attributes = new Attributes([
            'type' => 'text',
            'value' => CryptoHelper::getUniqueToken(20),
            'name' => $name,
            'id' => $id,
            'aria-hidden' => 'true',
            'autocomplete' => 'off',
            'tabindex' => '-1',
        ]);

        return <<<EOS
            <div class="{$id} ff-optical" aria-hidden="true" tabindex="-1">
                <label data-ff-check aria-hidden="true" tabindex="-1" for="{$id}">Freeform Check</label>
                <input {$attributes} />
            </div>
            EOS;
    }

    public function addJsTestScript(RenderTagEvent $event): void
    {
        if (!$event->isGenerateTag()) {
            return;
        }

        $form = $event->getForm();
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return;
        }

        $event->addScript(__DIR__.'/../Scripts/js-test.js');
    }

    public function collectScripts(CollectScriptsEvent $event): void
    {
        $event->addScript('freeform.js-test', __DIR__.'/../Scripts/js-test.js');
    }

    public function attachToAjaxPayload(PrepareAjaxResponsePayloadEvent $event): void
    {
        $form = $event->getForm();
        $integration = $this->getIntegration($form);
        if (!$integration) {
            return;
        }

        $event->add('jsTest', ['name' => $integration->getInputName()]);
    }

    private function getIntegration(Form $form): ?JavascriptTest
    {
        if ($form->isDisabled()->javascriptTest) {
            return null;
        }

        $integration = $this->integrationsProvider->getSingleton($form, JavascriptTest::class);
        if (!$integration) {
            return null;
        }

        return $integration;
    }

    private function getSettingsService(): SettingsService
    {
        return $this->plugin()->settings;
    }
}
