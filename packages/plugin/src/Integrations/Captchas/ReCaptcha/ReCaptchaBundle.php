<?php

namespace Solspace\Freeform\Integrations\Captchas\ReCaptcha;

use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class ReCaptchaBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            Form::class,
            Form::EVENT_COLLECT_SCRIPTS,
            [$this, 'collectScripts'],
        );
    }

    public function collectScripts(CollectScriptsEvent $event): void
    {
        $event->addScript('recaptcha.v2-invisible', 'js/scripts/front-end/captchas/recaptcha/v2-invisible.js');
        $event->addScript('recaptcha.v2-checkbox', 'js/scripts/front-end/captchas/recaptcha/v2-checkbox.js');
        $event->addScript('recaptcha.v3', 'js/scripts/front-end/captchas/recaptcha/v3.js');
    }
}
