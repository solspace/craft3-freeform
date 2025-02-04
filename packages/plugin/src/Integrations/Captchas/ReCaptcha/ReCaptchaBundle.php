<?php

namespace Solspace\Freeform\Integrations\Captchas\ReCaptcha;

use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class ReCaptchaBundle extends FeatureBundle
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
        $event->addScript('recaptcha.v2-invisible', 'js/scripts/front-end/captchas/recaptcha/v2-invisible.js');
        $event->addScript('recaptcha.v2-checkbox', 'js/scripts/front-end/captchas/recaptcha/v2-checkbox.js');
        $event->addScript('recaptcha.v3', 'js/scripts/front-end/captchas/recaptcha/v3.js');
    }

    public function attachToJson(OutputAsJsonEvent $event): void
    {
        $form = $event->getForm();
        $integrations = $this->integrationsProvider->getForForm($form, Type::TYPE_CAPTCHAS);
        if (!$integrations) {
            return;
        }

        foreach ($integrations as $integration) {
            if (!$integration instanceof ReCaptcha) {
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
             * - Add reCaptchas (Support multiples)
             * - Deprecate captcha, name, value and enabled and remove in version 6
             */
            $event->add('captcha', [
                'name' => 'g-recaptcha-response',
                'handle' => 'captcha',
                'enabled' => true,
                'action' => $integration->getAction(),
                'errorMessage' => $integration->getErrorMessage(),
                'failureBehavior' => $integration->getFailureBehavior(),
                'fieldHandle' => 'g-recaptcha-response',
                'locale' => $integration->getLocale(),
                'scoreThreshold' => $integration->getScoreThreshold(),
                'size' => $integration->getSize(),
                'theme' => $integration->getTheme(),
                'triggerOnInteract' => $integration->isTriggerOnInteract(),
                'version' => $integration->getVersion(),
            ]);

            return;
        }
    }
}
