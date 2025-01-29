<?php

namespace Solspace\Freeform\Integrations\Elements\User\EventListeners;

use Solspace\Freeform\Events\Integrations\ElementIntegrations\ProcessValueEvent;
use Solspace\Freeform\Integrations\Elements\User\User;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class PasswordTransform extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            User::class,
            User::EVENT_PROCESS_VALUE,
            [$this, 'processPassword']
        );
    }

    public function processPassword(ProcessValueEvent $event): void
    {
        if (!$event->getIntegration() instanceof User) {
            return;
        }

        if ('newPassword' !== $event->getHandle()) {
            return;
        }

        $value = $event->getValue();
        if (empty($value)) {
            $event->setValue(null);
        }
    }
}
