<?php

namespace Solspace\Freeform\Bundles\Form\Tracking;

use Solspace\Freeform\Bundles\Form\Limiting\FormLimiting;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class Cookies extends FeatureBundle
{
    private const COOKIE_BEHAVIORS = [
        FormLimiting::LIMIT_ONCE_PER_USER_OR_COOKIE,
        FormLimiting::LIMIT_ONCE_PER_USER_OR_IP_OR_COOKIE,
    ];

    public function __construct()
    {
        Event::on(Form::class, Form::EVENT_AFTER_SUBMIT, [$this, 'setPostedCookie']);
    }

    public static function getCookieName(Form $form): string
    {
        return 'form_posted_'.$form->getId();
    }

    public function setPostedCookie(SubmitEvent $event): void
    {
        if (\Craft::$app->request->isConsoleRequest) {
            return;
        }

        $form = $event->getForm();
        $behaviorSettings = $form->getSettings()->getBehavior();
        $behavior = $behaviorSettings->duplicateCheck;

        if (!\in_array($behavior, self::COOKIE_BEHAVIORS, true)) {
            return;
        }

        $name = self::getCookieName($form);
        $value = time();

        setcookie(
            $name,
            $value,
            [
                'expires' => (int) strtotime('+1 year'),
                'path' => '/',
                'domain' => \Craft::$app->getConfig()->getGeneral()->defaultCookieDomain,
                'secure' => true,
                'httponly' => true,
                'samesite' => \Craft::$app->getConfig()->getGeneral()->sameSiteCookieValue ?? 'Lax',
            ]
        );

        $_COOKIE[$name] = $value;
    }
}
