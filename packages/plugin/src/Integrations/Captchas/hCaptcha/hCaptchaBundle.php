<?php

namespace Solspace\Freeform\Integrations\Captchas\hCaptcha;

use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class hCaptchaBundle extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider
    ) {
        Event::on(
            Form::class,
            Form::EVENT_COLLECT_SCRIPTS,
            [$this, 'collectScripts'],
        );

        Event::on(
            Form::class,
            Form::EVENT_OUTPUT_AS_JSON,
            [$this, 'attachToJson']
        );
    }

    public function collectScripts(CollectScriptsEvent $event): void
    {
        $event->addScript('hcaptcha.invisible', 'js/scripts/front-end/captchas/hcaptcha/invisible.js');
        $event->addScript('hcaptcha.checkbox', 'js/scripts/front-end/captchas/hcaptcha/checkbox.js');
    }

    public function attachToJson(OutputAsJsonEvent $event): void
    {
        $form = $event->getForm();
        $integrations = $this->integrationsProvider->getForForm($form, Type::TYPE_CAPTCHAS);
        if (!$integrations) {
            return;
        }

        foreach ($integrations as $integration) {
            if (!$integration instanceof hCaptcha) {
                continue;
            }

            if (!$integration->isEnabled()) {
                continue;
            }

            $integration = reset($integrations);
            if (!$integration) {
                return;
            }

            /*
             * FIXME
             * - Add hCaptchas (Support multiples)
             * - Deprecate captcha, name, value and enabled and remove in version 6
             */
            $event->add('captcha', [
                'name' => 'h-captcha-response',
                'handle' => 'captcha',
                'enabled' => true,
                'action' => null,
                'errorMessage' => $integration->getErrorMessage(),
                'failureBehavior' => $integration->getFailureBehavior(),
                'fieldHandle' => 'h-captcha-response',
                'locale' => $integration->getLocale(),
                'scoreThreshold' => null,
                'size' => $integration->getSize(),
                'theme' => $integration->getTheme(),
                'triggerOnInteract' => $integration->isTriggerOnInteract(),
                'version' => $integration->getVersion(),
            ]);

            return;
        }
    }
}
