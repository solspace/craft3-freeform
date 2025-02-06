<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Bundles\Notifications\SendListeners;

use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\SendNotificationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Notifications\Types\Admin\Admin;
use yii\base\Event;

class AdminNotifications extends FeatureBundle
{
    public function __construct(
        private NotificationsProvider $notificationsProvider,
        private FreeformQueueHandler $queueHandler,
    ) {
        Event::on(Form::class, Form::EVENT_SEND_NOTIFICATIONS, [$this, 'sendToRecipients']);
    }

    public function sendToRecipients(SendNotificationsEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        if ($form->isDisabled()->adminNotifications) {
            return;
        }

        $notifications = $this->notificationsProvider->getByFormAndClass($form, Admin::class);
        if (!$notifications) {
            return;
        }

        $postedData = $event->getSubmission()->getFormFieldValues();

        foreach ($notifications as $notification) {
            $recipients = $notification->getRecipients();
            $template = $notification->getTemplate();

            $this->queueHandler->executeNotificationJob(
                new SendNotificationsJob([
                    'formId' => $form->getId(),
                    'submissionId' => $event->getSubmission()->id,
                    'postedData' => $postedData,
                    'recipients' => $recipients,
                    'template' => $template,
                    'notificationId' => $notification->getId(),
                ])
            );
        }
    }
}
