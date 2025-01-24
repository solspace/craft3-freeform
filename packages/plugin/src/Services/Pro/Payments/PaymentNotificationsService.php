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

namespace Solspace\Freeform\Services\Pro\Payments;

use craft\base\Component;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Properties\PaymentProperties;
use Solspace\Freeform\Records\Pro\Payments\PaymentRecord;

class PaymentNotificationsService extends Component
{
    public function sendChargeSucceeded(int $submissionId)
    {
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_CHARGE_SUCCEEDED);
    }

    public function sendChargeFailed(int $submissionId)
    {
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_CHARGE_FAILED);
    }

    public function sendSubscriptionCreated(int $submissionId)
    {
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_CREATED);
    }

    public function sendSubscriptionEnded(int $submissionId)
    {
        // TODO: move status update somewhere nice
        Freeform::getInstance()->subscriptions->updateSubscriptionStatus($submissionId, PaymentRecord::STATUS_INACTIVE);
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_ENDED);
    }

    public function sendSubscriptionPaymentSucceeded(int $submissionId)
    {
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_SUCCEEDED);
    }

    public function sendSubscriptionPaymentFailed(int $submissionId)
    {
        $this->send($submissionId, PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_FAILED);
    }

    protected function send(int $submissionId, string $notificationType)
    {
        // TODO: add error handling and logging
        $submission = Freeform::getInstance()->submissions->getSubmissionById($submissionId);
        $form = $submission->getForm();
        $paymentProps = $form->getPaymentProperties();
        $customerMap = $paymentProps->getCustomerFieldMapping();
        // TODO: hardcoded  string  is  bad, also stripe prefix, me dont like it
        $emailFieldHandle = $customerMap['email'] ?? null;
        if (!$emailFieldHandle) {
            return;
        }

        $email = $submission->{$emailFieldHandle}->getValue();
        $notifications = $paymentProps->getPaymentNotifications();
        if (isset($notifications[PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_SUCCEEDED])) {
            $notifications[PaymentProperties::NOTIFICATION_TYPE_CHARGE_SUCCEEDED] = $notifications[PaymentProperties::NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_SUCCEEDED];
        }

        $notification = Freeform::getInstance()
            ->notifications
            ->requireNotification(
                $form,
                $notifications[$notificationType] ?? null,
                'Payment Notification: '.$notificationType
            )
        ;

        $fields = $form->getLayout()->getFields();
        Freeform::getInstance()->mailer->sendEmail(
            $form,
            $email,
            $notification,
            $fields,
            $submission
        );
    }
}
