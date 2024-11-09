<?php

namespace Solspace\Freeform\Integrations\Captchas\hCaptcha;

use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class hCaptchaBundle extends FeatureBundle
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
        $event->addScript('hcaptcha.invisible', 'js/scripts/front-end/captchas/hcaptcha/invisible.js');
        $event->addScript('hcaptcha.checkbox', 'js/scripts/front-end/captchas/hcaptcha/checkbox.js');
    }
}
