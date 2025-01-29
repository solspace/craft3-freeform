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
use Solspace\Freeform\Notifications\Types\Dynamic\Dynamic;
use yii\base\Event;

class DynamicRecipients extends FeatureBundle
{
    public function __construct(
        private NotificationsProvider $notificationsProvider,
        private FreeformQueueHandler $queueHandler
    ) {
        Event::on(Form::class, Form::EVENT_SEND_NOTIFICATIONS, [$this, 'sendToRecipients']);
    }

    public function sendToRecipients(SendNotificationsEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        if ($form->isDisabled()->userSelectNotifications) {
            return;
        }

        $notifications = $this->notificationsProvider->getByFormAndClass($form, Dynamic::class);
        if (!$notifications) {
            return;
        }

        $postedData = $event->getSubmission()->getFormFieldValues();

        foreach ($notifications as $notification) {
            $field = $form->get($notification->getField());
            if (!$field) {
                continue;
            }

            $value = $field->getValue();
            if (!\is_array($value)) {
                $value = [$value];
            }

            $defaultTemplate = $notification->getTemplate();
            $defaultRecipients = $notification->getRecipients();

            $recipientMapping = $notification->getRecipientMapping();
            foreach ($value as $selectedValue) {
                $mapping = $recipientMapping->getMappingByValue($selectedValue);

                $template = $defaultTemplate;
                $recipients = $defaultRecipients;
                if ($mapping) {
                    $template = $mapping->getTemplate() ?? $template;
                    $recipients = $mapping->getRecipients()->count() ? $mapping->getRecipients() : $recipients;
                }

                if (!$recipients->emailsToArray() || !$template) {
                    continue;
                }

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
}
