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

use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\SendNotificationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Notifications\Components\Recipients\Recipient;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use yii\base\Event;

class DynamicTemplateRecipients extends FeatureBundle
{
    public const BAG_KEY = 'dynamicNotification';

    public function __construct(private FreeformQueueHandler $queueHandler)
    {
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

        $data = $form->getProperties()->get(self::BAG_KEY);

        $template = $data['template'] ?? null;
        $recipients = $data['recipients'] ?? [];

        if (empty($recipients) || !$template) {
            return;
        }

        $notification = $this->plugin()->notifications->requireNotification($form, $template, 'Dynamic Notification from template params');
        $recipientCollection = new RecipientCollection();

        if (\is_array($recipients)) {
            foreach ($recipients as $recipient) {
                $recipientCollection->add(new Recipient($recipient, ''));
            }
        } else {
            $recipientCollection->add(new Recipient($recipients, ''));
        }

        $notificationTemplate = NotificationTemplate::fromRecord($notification);

        $postedData = $event->getSubmission()->getFormFieldValues();

        $this->queueHandler->executeNotificationJob(
            new SendNotificationsJob([
                'formId' => $form->getId(),
                'submissionId' => $event->getSubmission()->id,
                'postedData' => $postedData,
                'recipients' => $recipientCollection,
                'template' => $notificationTemplate,
            ])
        );
    }
}
