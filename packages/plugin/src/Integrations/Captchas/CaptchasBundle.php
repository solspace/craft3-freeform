<?php

namespace Solspace\Freeform\Integrations\Captchas;

use Solspace\Freeform\Bundles\GraphQL\Resolvers\CaptchaResolver;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Forms\SetPropertiesEvent;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Events\Integrations\RegisterIntegrationTypesEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Captchas\hCaptcha\hCaptcha;
use Solspace\Freeform\Integrations\Captchas\ReCaptcha\ReCaptcha;
use Solspace\Freeform\Integrations\Captchas\Turnstile\Turnstile;
use Solspace\Freeform\Integrations\Other\FormMonitor\Providers\FormMonitorProvider;
use Solspace\Freeform\Library\Attributes\Attributes;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\Types\Captchas\CaptchaIntegrationInterface;
use Solspace\Freeform\Services\Integrations\IntegrationsService;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Event;

class CaptchasBundle extends FeatureBundle
{
    public const KEY_CAPTCHA_PROPERTIES = 'captchas';
    public const KEY_CAPTCHA_PROPERTY_STACK = 'captchaPropertyStack';

    private bool $renderedManually = false;

    public function __construct(
        private FormIntegrationsProvider $formIntegrationsProvider,
        private SettingsService $settingsService,
        private FormMonitorProvider $formMonitorProvider,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_RENDER_CAPTCHAS,
            [$this, 'renderCaptchaManually'],
        );

        Event::on(
            Form::class,
            Form::EVENT_RENDER_BEFORE_CLOSING_TAG,
            [$this, 'attachHtmlElement'],
        );

        Event::on(
            Form::class,
            Form::EVENT_RENDER_BEFORE_CLOSING_TAG,
            [$this, 'attachScripts'],
        );

        Event::on(
            Form::class,
            Form::EVENT_BEFORE_VALIDATE,
            [$this, 'triggerValidation']
        );

        Event::on(
            IntegrationsService::class,
            IntegrationsService::EVENT_REGISTER_INTEGRATION_TYPES,
            [$this, 'registerTypes']
        );

        Event::on(
            Form::class,
            Form::EVENT_SET_PROPERTIES,
            [$this, 'processOptions']
        );

        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this, 'attachToJson']
        );
    }

    public function renderCaptchaManually(RenderTagEvent $event): void
    {
        if ($this->attachHtmlElement($event)) {
            $this->renderedManually = true;
        }
    }

    public function attachHtmlElement(RenderTagEvent $event): bool
    {
        if ($this->renderedManually) {
            return false;
        }

        $form = $event->getForm();
        if (!$form->isLastPage()) {
            return false;
        }

        if ($this->formMonitorProvider->isRequestFromFormMonitor($form)) {
            return false;
        }

        $integrations = $this->getCaptchasForForm($form);
        if (!$integrations) {
            return false;
        }

        foreach ($integrations as $integration) {
            $event->addChunk(
                $integration->getHtmlTag($form),
                position: RenderTagEvent::POSITION_BEGINNING
            );
        }

        return true;
    }

    public function attachScripts(RenderTagEvent $event): void
    {
        if (!$event->isGenerateTag()) {
            return;
        }

        $form = $event->getForm();
        if ($this->formMonitorProvider->isRequestFromFormMonitor($form)) {
            return;
        }

        $integrations = $this->getCaptchasForForm($form);
        if (!$integrations) {
            return;
        }

        foreach ($integrations as $integration) {
            $scriptPaths = $integration->getScriptPaths();
            foreach ($scriptPaths as $scriptPath) {
                $event->addScript($scriptPath);
            }
        }
    }

    public function triggerValidation(ValidationEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $settingsModel = $this->settingsService->getSettingsModel();
        if ($settingsModel->bypassSpamCheckOnLoggedInUsers && \Craft::$app->getUser()->id) {
            return;
        }

        $form = $event->getForm();
        if (!$form->isLastPage()) {
            return;
        }

        if ($this->formMonitorProvider->isRequestFromFormMonitor($form)) {
            return;
        }

        $integrations = $this->getCaptchasForForm($form);
        foreach ($integrations as $integration) {
            $integration->validate($form);
        }
    }

    public function attachToJson(OutputAsJsonEvent $event): void
    {
        $captchas = [];

        $integrations = $this->getCaptchasForForm($event->getForm());
        if (!$integrations) {
            return;
        }

        $enabled = array_filter($integrations, fn ($integration) => $integration->isEnabled());
        if (!$enabled) {
            return;
        }

        foreach ($integrations as $integration) {
            if (!$integration->isEnabled()) {
                continue;
            }

            $captchas[] = CaptchaResolver::getArguments($integration);
        }

        if (empty($captchas)) {
            return;
        }

        $event->add('captchas', $captchas);
    }

    public static function getCaptchaAttributes(Form $form): Attributes
    {
        $bag = $form->getProperties();
        $properties = $bag->get(self::KEY_CAPTCHA_PROPERTY_STACK, []);

        $attributes = new Attributes();
        foreach ($properties as $stack) {
            $attributes->merge($stack);
        }

        return $attributes;
    }

    public function registerTypes(RegisterIntegrationTypesEvent $event): void
    {
        $event->addType(ReCaptcha::class);
        $event->addType(hCaptcha::class);
        $event->addType(Turnstile::class);
    }

    public function processOptions(SetPropertiesEvent $event): void
    {
        $properties = $event->getProperties();
        if (!isset($properties[self::KEY_CAPTCHA_PROPERTIES])) {
            return;
        }

        $props = $properties[self::KEY_CAPTCHA_PROPERTIES];

        // Get the current property stack and add to the stack
        $form = $event->getForm();
        $stack = $form->getProperties()->get(self::KEY_CAPTCHA_PROPERTY_STACK, []);
        foreach ($stack as $stackItem) {
            if ($stackItem === $props) {
                return;
            }
        }

        $stack[] = $props;
        $form->getProperties()->set(self::KEY_CAPTCHA_PROPERTY_STACK, $stack);

        // Remove from current properties
        unset($properties[self::KEY_CAPTCHA_PROPERTIES]);
        $event->setProperties($properties);
    }

    /**
     * @return CaptchaIntegrationInterface[]
     */
    private function getCaptchasForForm(Form $form): array
    {
        if ($form->isDisabled()->captchas) {
            return [];
        }

        return $this->formIntegrationsProvider->getForForm($form, CaptchaIntegrationInterface::class);
    }
}
