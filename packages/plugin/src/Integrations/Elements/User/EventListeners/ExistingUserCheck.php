<?php

namespace Solspace\Freeform\Integrations\Elements\User\EventListeners;

use craft\elements\User as CraftUser;
use Solspace\Freeform\Events\Integrations\ElementIntegrations\ValidateEvent;
use Solspace\Freeform\Integrations\Elements\User\User;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\Types\Elements\ElementIntegrationInterface;
use yii\base\Event;

class ExistingUserCheck extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            ElementIntegrationInterface::class,
            ElementIntegrationInterface::EVENT_AFTER_VALIDATE,
            [$this, 'checkForExistingUser']
        );
    }

    public function checkForExistingUser(ValidateEvent $event): void
    {
        $integration = $event->getIntegration();
        if (!$integration instanceof User) {
            return;
        }

        $element = $event->getElement();
        if (!$element instanceof CraftUser) {
            return;
        }

        if ($element->id) {
            return;
        }

        $mapping = $integration->getAttributeMapping();
        $isEmailMapped = $mapping->isSourceMapped('email');
        $isUsernameMapped = $mapping->isSourceMapped('username');

        $userService = \Craft::$app->getUsers();
        if ($isUsernameMapped && $userService->getUserByUsernameOrEmail($element->username)) {
            $element->addError('username', \Craft::t('app', 'Username already exists.'));

            return;
        }

        if ($isEmailMapped && $userService->getUserByUsernameOrEmail($element->email)) {
            $element->addError('email', \Craft::t('app', 'Email is in use already.'));
        }
    }
}
