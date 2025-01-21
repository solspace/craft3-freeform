<?php

namespace Solspace\Freeform\Integrations\PaymentGateways\Stripe\Services;

use craft\helpers\UrlHelper;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\PaymentGateways\Events\UpdateMetadataEvent;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Fields\StripeField;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Stripe;
use Solspace\Freeform\Records\Pro\Payments\PaymentRecord;
use Solspace\Freeform\Records\SavedFormRecord;
use Solspace\Freeform\Services\SubmissionsService;
use Stripe\PaymentIntent;
use yii\base\Event;

class StripeCallbackService
{
    public function __construct(
        private SubmissionsService $submissionsService,
        private IntegrationLoggerProvider $loggerProvider,
    ) {}

    public function handleSavedForm(
        Form $form,
        Stripe $integration,
        StripeField $field,
        PaymentIntent $paymentIntent,
        ?SavedFormRecord $savedForm,
    ): bool {
        $logger = $this->loggerProvider->getLogger($integration);
        $logger->debug('Handling saved form', ['form' => $form->getHandle(), 'paymentIntent' => $paymentIntent->id]);

        $stripe = $integration->getStripeClient();
        $payment = PaymentRecord::findOne([
            'fieldId' => $field->getId(),
            'integrationId' => $integration->getId(),
            'resourceId' => $paymentIntent->id,
        ]);

        if (!$payment && $savedForm) {
            $logger->debug('Handling saved form with payment intent');

            $savedForm->delete();

            $payload = json_decode(
                \Craft::$app->security->decryptByKey(
                    base64_decode($savedForm->payload),
                    $paymentIntent->client_secret
                ),
                true
            );

            $paymentIntent = $stripe->paymentIntents->retrieve(
                $paymentIntent->id,
                ['expand' => ['customer', 'payment_method', 'invoice.subscription.plan.product']]
            );

            $form->quickLoad($payload);
            $this->submissionsService->handleSubmission($form);

            $type = null !== $paymentIntent->invoice ? 'subscription' : 'payment';

            if (!$form->getSubmission()->id) {
                $logger->debug('Submission not found', ['form' => $form->getHandle(), 'paymentIntent' => $paymentIntent->id]);

                return false;
            }

            $payment = new PaymentRecord();
            $payment->integrationId = $integration->getId();
            $payment->fieldId = $field->getId();
            $payment->submissionId = $form->getSubmission()->id;
            $payment->resourceId = $paymentIntent->id;
            $payment->type = $type;
            $payment->currency = $paymentIntent->currency;
            $payment->amount = $paymentIntent->amount;

            $logger->debug('Payment record created');
        }

        if (!$payment) {
            $logger->debug('Payment record not found. Exiting.');

            return false;
        }

        $payment->status = $paymentIntent->status;
        $payment->link = $this->generateLink($paymentIntent, $integration);

        $metadata = [];
        $paymentMethod = null;
        if ($paymentIntent->last_payment_error) {
            $error = $paymentIntent->last_payment_error->toArray();
            unset($error['payment_method']);

            $paymentMethod = $paymentIntent->last_payment_error->payment_method;
            $metadata['error'] = $error;

            $logger->debug('Payment error', ['error' => $error]);
        } elseif ($paymentIntent->payment_method) {
            $paymentMethod = $paymentIntent->payment_method;
        }

        if ($paymentMethod) {
            if (\is_string($paymentMethod)) {
                $paymentMethod = $stripe->paymentMethods->retrieve($paymentMethod);
            }

            $metadata['type'] = $paymentMethod->type;
            $metadata['details'] = $paymentMethod->{$paymentMethod->type}->toArray();

            $logger->debug('Payment method found', ['paymentMethod' => $paymentMethod->id]);
        }

        $planName = $paymentIntent?->invoice?->subscription?->plan?->product?->name ?? null;
        if ($planName) {
            $metadata['planName'] = $planName;
            $metadata['interval'] = $paymentIntent?->invoice?->subscription?->plan?->interval ?? null;
            $metadata['frequency'] = $paymentIntent?->invoice?->subscription?->plan?->interval_count ?? null;

            $logger->debug('Subscription plan found', ['planName' => $planName, 'interval' => $metadata['interval'], 'frequency' => $metadata['frequency']]);
        }

        $payment->metadata = $metadata;
        $payment->save();

        $logger->debug('Payment record saved', ['id' => $payment->id]);

        if ($savedForm) {
            $submissionMetadata = [
                'submission' => UrlHelper::cpUrl('freeform/submissions/'.$form->getSubmission()->id),
            ];

            $event = new UpdateMetadataEvent($form, $integration, $submissionMetadata);
            Event::trigger(Stripe::class, Stripe::EVENT_AFTER_UPDATE_PAYMENT_METADATA, $event);

            $logger->debug('Stripe payment metadata updated', ['submissionMetadata' => $submissionMetadata]);

            if ($paymentIntent?->invoice?->subscription) {
                $stripe->subscriptions->update(
                    $paymentIntent->invoice->subscription->id,
                    [
                        'metadata' => array_merge(
                            $paymentIntent->invoice->subscription->metadata->toArray(),
                            $submissionMetadata,
                        ),
                    ]
                );
            } else {
                $stripe->paymentIntents->update(
                    $paymentIntent->id,
                    [
                        'receipt_email' => $integration->isSendSuccessMail() ? $paymentIntent->customer->email : null,
                        'metadata' => array_merge(
                            $paymentIntent->metadata->toArray(),
                            $submissionMetadata,
                        ),
                    ]
                );
            }
        }

        return true;
    }

    private function generateLink(PaymentIntent $paymentIntent, Stripe $integration): string
    {
        $isTestEnv = str_starts_with($integration->getPublicKey(), 'pk_test_');
        $isSubscription = null !== $paymentIntent->invoice && null !== $paymentIntent->invoice->subscription;

        $base = $isTestEnv ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';
        $link = $base;
        if ($isSubscription) {
            $link .= 'subscriptions/';
            if (\is_string($paymentIntent->invoice->subscription)) {
                $link .= $paymentIntent->invoice->subscription;
            } else {
                $link .= $paymentIntent->invoice->subscription->id;
            }
        } else {
            $link .= 'payments/'.$paymentIntent->id;
        }

        return $link;
    }
}
