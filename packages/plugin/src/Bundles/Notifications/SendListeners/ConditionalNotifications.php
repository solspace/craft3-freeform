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
use Solspace\Freeform\Bundles\Rules\ConditionValidator;
use Solspace\Freeform\Events\Forms\SendNotificationsEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\SendNotificationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Rules\Types\NotificationRule;
use Solspace\Freeform\Notifications\Types\Conditional\Conditional;
use yii\base\Event;

class ConditionalNotifications extends FeatureBundle
{
    public function __construct(
        private NotificationsProvider $notificationsProvider,
        private ConditionValidator $conditionValidator,
        private FreeformQueueHandler $queueHandler
    ) {
        Event::on(
            Form::class,
            Form::EVENT_SEND_NOTIFICATIONS,
            [$this, 'sendToRecipients']
        );
    }

    public static function isProOnly(): bool
    {
        return true;
    }

    public function sendToRecipients(SendNotificationsEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        if ($form->isDisabled()->conditionalNotifications) {
            return;
        }

        $notifications = $this->notificationsProvider->getByFormAndClass($form, Conditional::class);
        if (!$notifications) {
            return;
        }

        $postedData = $event->getSubmission()->getFormFieldValues();

        foreach ($notifications as $notification) {
            $recipients = $notification->getRecipients();
            if (!$recipients) {
                continue;
            }

            $template = $notification->getTemplate();
            if (!$template) {
                continue;
            }

            $rule = $notification->getRule();
            if (!$rule) {
                continue;
            }

            $conditions = $rule->getConditions();

            $matchesSome = false;
            $matchesAll = true;
            foreach ($conditions as $condition) {
                $field = $form->get($condition->getField());
                if (!$field) {
                    continue;
                }

                $postedValue = $field->getValue();

                $valueMatch = $this->conditionValidator->validate($condition, $postedValue);
                if ($valueMatch) {
                    $matchesSome = true;
                } else {
                    $matchesAll = false;
                }
            }

            $shouldSend = $rule->isSend();

            $triggers = match ($rule->getCombinator()) {
                NotificationRule::COMBINATOR_AND => $shouldSend ? $matchesAll : !$matchesAll,
                NotificationRule::COMBINATOR_OR => $shouldSend ? $matchesSome : !$matchesSome,
            };

            if (!$triggers) {
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
