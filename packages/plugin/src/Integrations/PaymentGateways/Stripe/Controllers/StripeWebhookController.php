<?php

namespace Solspace\Freeform\Integrations\PaymentGateways\Stripe\Controllers;

use Psr\Log\LoggerInterface;
use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Services\StripeCallbackService;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe\Stripe;
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
    private const SUPPORTED_EVENTS = [
        Event::PAYMENT_INTENT_CANCELED,
        Event::PAYMENT_INTENT_PAYMENT_FAILED,
        Event::PAYMENT_INTENT_SUCCEEDED,
    ];

    public $enableCsrfValidation = false;
    protected array|bool|int $allowAnonymous = ['webhooks'];
    private LoggerInterface $logger;

    public function __construct(
        $id,
        $module,
        $config,
        private StripeCallbackService $callbackService,
        IntegrationLoggerProvider $loggerProvider,
    ) {
        parent::__construct($id, $module, $config);

        $this->logger = $loggerProvider->getLogger(Stripe::class);
    }

    public function actionWebhooks(): Response
    {
        $payload = @file_get_contents('php://input');
        $json = json_decode($payload, false);
        $header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

        $this->logger->debug('Received a Stripe Webhook', json_decode($payload, true));

        if (!$json || !$header) {
            return $this->asEmptyResponse(400);
        }

        if (!\in_array($json->type, self::SUPPORTED_EVENTS, true)) {
            $this->logger->warning('Received unsupported Stripe event in Webhook', ['type' => $json->type]);

            return $this->asEmptyResponse();
        }

        try {
            $hash = $json->data->object->subscription_details->metadata->hash ?? $json->data->object->metadata->hash;
        } catch (\Exception $exception) {
            $this->logger->error('Received a Stripe Webhook that does not contain a valid hash');

            throw new BadRequestHttpException('Request did not contain a valid Freeform hash');
        }

        [, $integration] = $this->getRequestItems($hash);
        $secret = $integration->getWebhookSecret();

        try {
            $event = Webhook::constructEvent($payload, $header, $secret);
        } catch (\UnexpectedValueException $e) {
            $this->logger->error('Stripe Webhook error', ['error' => $e->getMessage()]);

            // Invalid payload
            return $this->asSerializedJson(['error' => $e->getMessage()], 400);
        } catch (SignatureVerificationException $e) {
            $this->logger->error('Stripe Webhook Signature error', ['error' => $e->getMessage()]);

            // Invalid signature
            return $this->asEmptyResponse(401);
        }

        $this->logger->info('Handling Stripe Webhook', ['type' => $event->type, 'id' => $event->id]);

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
            $this->logger->error('Stripe Webhook error', ['error' => $exception->getMessage()]);

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
