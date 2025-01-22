<?php

namespace Solspace\Freeform\Integrations\PaymentGateways\Stripe\Controllers;

use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Services\StripeCallbackService;
use Solspace\Freeform\Records\SavedFormRecord;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class StripeWebhookController extends BaseStripeController
{
    public $enableCsrfValidation = false;
    protected array|bool|int $allowAnonymous = ['webhooks'];

    public function __construct(
        $id,
        $module,
        $config,
        private StripeCallbackService $callbackService,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionWebhooks(): Response
    {
        $payload = @file_get_contents('php://input');
        $json = json_decode($payload, false);
        $header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        try {
            $hash = $json->data->object->subscription_details->metadata->hash ?? $json->data->object->metadata->hash;
        } catch (\Exception) {
            throw new BadRequestHttpException('Request did not contain a valid Freeform hash');
        }

        [, $integration] = $this->getRequestItems($hash);
        $secret = $integration->getWebhookSecret();

        try {
            $event = Webhook::constructEvent($payload, $header, $secret);
        } catch (\UnexpectedValueException $e) {
            // Invalid payload
            return $this->asSerializedJson(['error' => $e->getMessage()], 400);
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            return $this->asEmptyResponse(401);
        }

        return match ($event?->type) {
            Event::PAYMENT_INTENT_CANCELED,
            Event::PAYMENT_INTENT_PAYMENT_FAILED,
            Event::PAYMENT_INTENT_SUCCEEDED => $this->handlePaymentIntent($event),
            default => $this->asEmptyResponse(),
        };
    }

    protected function handlePaymentIntent(Event $event): Response
    {
        /** @var PaymentIntent $paymentIntent */
        $paymentIntent = $event->data->object;

        $hash = $paymentIntent?->metadata?->hash;

        try {
            [$form, $integration, $field] = $this->getRequestItems($hash);
        } catch (NotFoundHttpException $exception) {
            return $this->asSerializedJson(['errors' => [$exception->getMessage()]], 404);
        }

        $savedForm = SavedFormRecord::findOne([
            'token' => $paymentIntent->id,
            'formId' => $form->getId(),
        ]);

        $this->callbackService->handleSavedForm(
            $form,
            $integration,
            $field,
            $paymentIntent,
            $savedForm,
        );

        return $this->asEmptyResponse();
    }
}
