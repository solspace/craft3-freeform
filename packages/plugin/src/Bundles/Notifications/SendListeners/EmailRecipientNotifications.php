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
use Solspace\Freeform\Bundles\Rules\RuleValidator;
use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\SendNotificationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Notifications\Components\Recipients\Recipient;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use Solspace\Freeform\Notifications\Types\EmailField\EmailField;
use yii\base\Event;

class EmailRecipientNotifications extends FeatureBundle
{
    public function __construct(
        private NotificationsProvider $notificationsProvider,
        private FreeformQueueHandler $queueHandler,
        private RuleValidator $ruleValidator,
    ) {
        Event::on(Form::class, Form::EVENT_SEND_NOTIFICATIONS, [$this, 'sendToRecipients']);
    }

    public function sendToRecipients(SendNotificationsEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        if ($form->isDisabled()->emailFieldNotifications) {
            return;
        }

        $notifications = $this->notificationsProvider->getByFormAndClass($form, EmailField::class);
        if (!$notifications) {
            return;
        }

        $postedData = $event->getSubmission()->getFormFieldValues();

        foreach ($notifications as $notification) {
            $field = $form->get($notification->getField());
            if (!$field) {
                continue;
            }

            if ($this->ruleValidator->isFieldHidden($form, $field)) {
                continue;
            }

            $recipient = $field->getValue();
            $notificationTemplate = $notification->getTemplate();

            $recipientCollection = new RecipientCollection();
            $recipientCollection->add(new Recipient($recipient));

            $this->queueHandler->executeNotificationJob(
                new SendNotificationsJob([
                    'formId' => $form->getId(),
                    'submissionId' => $event->getSubmission()->id,
                    'postedData' => $postedData,
                    'recipients' => $recipientCollection,
                    'template' => $notificationTemplate,
                    'notificationId' => $notification->getId(),
                ])
            );
        }
    }
}
