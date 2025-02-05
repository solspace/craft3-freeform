<?php

namespace Solspace\Freeform\Integrations\Captchas\Turnstile;

use Solspace\Freeform\Events\Forms\CollectScriptsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class TurnstileBundle extends FeatureBundle
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
        $event->addScript('turnstile', 'js/scripts/front-end/captchas/turnstile/v0.js');
    }
}
