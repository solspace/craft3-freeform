<?php

namespace Solspace\Freeform\controllers\integrations\payments;

use Solspace\Freeform\Controllers\BaseController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Stripe;
use Stripe\Error\SignatureVerification;
use Stripe\Event;
use Stripe\Webhook;
use yii\web\HttpException;

// TODO: create abstract controller
class WebhooksController extends BaseController
{
    public $enableCsrfValidation = false;

    protected array|bool|int $allowAnonymous = true;

    public function actionStripe()
    {
        $this->requirePostRequest();

        $request = \Craft::$app->request;
        $payload = $request->getRawBody();
        $integrationId = $request->getQueryParam('id');

        /** @var Stripe $integration */
        $integration = $this->getPaymentGatewaysService()->getIntegrationObjectById($integrationId);

        if (!$integration) {
            throw new HttpException(400, Freeform::t('Invalid integration'));
        }

        $endpointSecret = $integration->getWebhookSecret();

        if (!$endpointSecret) {
            throw new HttpException(400, Freeform::t('Integration is not configured properly'));
        }

        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            throw new HttpException(400, Freeform::t('Invalid payload'));
        } catch (SignatureVerification $e) {
            throw new HttpException(400, Freeform::t('Invalid signature'));
        }

        // TODO: implement all notification service call as events?
        // TODO: update payment/subscription status accordingly
        switch ($event->type) {
            case Event::CHARGE_SUCCEEDED:
                $submissionId = $this->getSubmissionIdFromStripeEvent($event, $integration);
                $this->getPaymentsNotificationService()->sendChargeSucceeded($submissionId);

                break;

            case Event::CHARGE_FAILED:
                $submissionId = $this->getSubmissionIdFromStripeEvent($event, $integration);
                if ($submissionId) {
                    $this->getPaymentsNotificationService()->sendChargeFailed($submissionId);
                }

                break;

            case Event::CUSTOMER_SUBSCRIPTION_CREATED:
                $submissionId = $this->getSubmissionIdFromStripeEvent($event, $integration);
                $this->getPaymentsNotificationService()->sendSubscriptionCreated($submissionId);

                break;

            case Event::CUSTOMER_SUBSCRIPTION_DELETED:
                $submissionId = $this->getSubmissionIdFromStripeEvent($event, $integration);
                $this->getPaymentsNotificationService()->sendSubscriptionEnded($submissionId);

                break;

            case Event::INVOICE_PAYMENT_SUCCEEDED:
                $subscriptionId = $event->data->object->lines->data[0]->subscription;
                $subscription = $integration->getSubscriptionDetails($subscriptionId);
                if (!$subscription) {
                    throw new HttpException(400, 'Could not send successful payment notification');
                }

                $submissionId = $subscription['metadata']['submissionId'] ?? null;
                if (!$submissionId) {
                    throw new HttpException(400, 'Missing submissions ID in payment details');
                }

                $this->getPaymentsNotificationService()->sendSubscriptionPaymentSucceeded($submissionId);

                break;

            case Event::INVOICE_PAYMENT_FAILED:
                $subscriptionId = $event->data->object->lines->data[0]->subscription;
                $subscription = $integration->getSubscriptionDetails($subscriptionId);
                if (!$subscription) {
                    throw new HttpException(400, 'Could not send failed payment notification');
                }

                $submissionId = $subscription['metadata']['submissionId'] ?? null;
                if (!$submissionId) {
                    throw new HttpException(400, 'Missing submissions ID in payment details');
                }

                $this->getPaymentsNotificationService()->sendSubscriptionPaymentFailed($submissionId);

                break;

            default:
        }

        return '';
    }

    /**
     * @return mixed
     *
     * @throws HttpException
     */
    private function getSubmissionIdFromStripeEvent(Event $event, Stripe $integration, bool $suppressError = false)
    {
        $submissionId = $event->data->object->metadata->submission;
        if ($submissionId) {
            return $submissionId;
        }

        $subscriptionId = $event->data->object->id;
        if ($subscriptionId) {
            $subscription = $integration->getSubscriptionDetails($subscriptionId);

            if (isset($subscription['metadata']['submissionId'])) {
                return $subscription['metadata']['submissionId'];
            }
        }

        $paymentIntentId = $event->data->object->payment_intent;
        if ($paymentIntentId) {
            $paymentIntent = $integration->getPaymentIntentDetails($paymentIntentId);

            if ($paymentIntent) {
                return $paymentIntent->metadata->submissionId;
            }
        }

        if ($suppressError) {
            return null;
        }

        throw new HttpException(400, Freeform::t('Event is not linked to Freeform submission'));
    }
}
