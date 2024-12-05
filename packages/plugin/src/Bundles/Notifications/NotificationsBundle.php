<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Bundles\Notifications;

use Solspace\Freeform\Bundles\Notifications\Providers\NotificationTypesProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Events\Notifications\RegisterNotificationTypesEvent;
use Solspace\Freeform\Events\Submissions\ProcessSubmissionEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Notifications\Types\Admin\Admin;
use Solspace\Freeform\Notifications\Types\Conditional\Conditional;
use Solspace\Freeform\Notifications\Types\Dynamic\Dynamic;
use Solspace\Freeform\Notifications\Types\EmailField\EmailField;
use yii\base\Event;

class NotificationsBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            Submission::class,
            Submission::EVENT_PROCESS_SUBMISSION,
            [$this, 'sendNotifications']
        );

        Event::on(
            NotificationTypesProvider::class,
            NotificationTypesProvider::EVENT_REGISTER_NOTIFICATION_TYPES,
            [$this, 'registerNotificationTypes']
        );
    }

    public function sendNotifications(ProcessSubmissionEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        $submission = $event->getSubmission();

        if ($form->isMarkedAsSpam()) {
            return;
        }

        $event = new SendNotificationsEvent($form, $submission, $this->plugin()->mailer);
        Event::trigger(Form::class, Form::EVENT_SEND_NOTIFICATIONS, $event);
    }

    public function registerNotificationTypes(RegisterNotificationTypesEvent $event): void
    {
        $event->addType(Admin::class);
        $event->addType(EmailField::class);

        if ($this->plugin()->edition()->isAtLeast(Freeform::EDITION_LITE)) {
            $event->addType(Dynamic::class);
        }

        if ($this->plugin()->edition()->isAtLeast(Freeform::EDITION_PRO)) {
            $event->addType(Conditional::class);
        }
    }
}
