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
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Fields\Pro\Payments\CreditCardDetailsField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\PaymentGateways\Actions\Stripe\SinglePaymentAction;
use Solspace\Freeform\Integrations\PaymentGateways\Actions\Stripe\SubscriptionPaymentIntentAction;
use Solspace\Freeform\Integrations\PaymentGateways\Stripe;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\PaymentInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Composer\Components\Page;
use Solspace\Freeform\Library\Composer\Components\Properties\PaymentProperties;
use Solspace\Freeform\Library\DataObjects\CustomerDetails;
use Solspace\Freeform\Library\DataObjects\PlanDetails;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Models\IntegrationModel;
use Stripe\Customer;
use Stripe\Invoice;
use Stripe\PaymentIntent;
use Stripe\Plan;
use Stripe\Subscription;

class StripeService extends Component
{
    public const FIELD_GROUP_TYPES = [FieldInterface::TYPE_CHECKBOX_GROUP, FieldInterface::TYPE_RADIO_GROUP];

    public function preProcessPayment(ValidationEvent $event)
    {
        $form = $event->getForm();
        $properties = $form->getPaymentProperties();
        $integrationId = $properties->getIntegrationId();
        $paymentType = $properties->getPaymentType();

        if (!$integrationId || PaymentProperties::PAYMENT_TYPE_SINGLE !== $paymentType || !$form->isValid() || !$this->hasPaymentFieldDisplayed($form)) {
            return;
        }

        $paymentField = $this->getPaymentField($form);
        $dynamicValues = $this->getDynamicFieldValues($form, $properties);
        $integration = $this->getIntegrationObjectById($properties);

        $token = $paymentField->getValue();
        if (!$token) {
            return;
        }

        $integration->prepareApi();
        if (0 === strpos($token, 'pm_')) {
            $currency = $dynamicValues[PaymentProperties::FIELD_CURRENCY] ?? $properties->getCurrency();
            $amount = $dynamicValues[PaymentProperties::FIELD_AMOUNT] ?? $properties->getAmount();

            if (!\in_array(strtoupper($currency), Stripe::ZERO_DECIMAL_CURRENCIES)) {
                if (\function_exists('bcmul')) {
                    $amount = (int) ceil(bcmul($amount, 100));
                } else {
                    $amount = (int) ceil($amount * 100);
                }
            }

            $customerData = CustomerDetails::fromArray($dynamicValues)->toStripeConstructArray();
            $customerData['payment_method'] = $token;

            $paymentIntentProperties = [
                'payment_method' => $token,
                'amount' => $amount,
                'currency' => $currency,
                'confirmation_method' => 'manual',
                'confirm' => true,
            ];

            $mapping = $properties->getCustomerFieldMapping();
            if (isset($mapping['email']) && $form->get($mapping['email'])) {
                if ($integration->sendOnSuccess()) {
                    $paymentIntentProperties['receipt_email'] = $form->get($mapping['email'])->getValueAsString();
                }
            }

            try {
                $customer = Customer::create($customerData);

                $paymentIntentProperties['customer'] = $customer->id;

                $paymentIntent = PaymentIntent::create($paymentIntentProperties);
            } catch (\Stripe\Exception\CardException $e) {
                $form->addError(Freeform::t($e->getMessage()));

                $paymentField->setValue('declined: '.$e->getMessage().' code: '.$e->getStripeCode().'. decline_code: '.$e->getDeclineCode());

                return;
            } catch (\Stripe\Exception\ApiErrorException $e) {
                $paymentField->setValue('declined: '.$e->getMessage());

                return;
            } catch (\Exception $e) {
                $paymentField->setValue('declined: '.$e->getMessage());

                return;
            }
        } else {
            $paymentIntent = PaymentIntent::retrieve($token);
        }

        if (PaymentIntent::STATUS_REQUIRES_ACTION === $paymentIntent->status) {
            $form->addAction(new SinglePaymentAction($paymentIntent));
        }

        $paymentField->setValue($paymentIntent->id);
    }

    public function preProcessSubscription(ValidationEvent $event)
    {
        $form = $event->getForm();
        $properties = $form->getPaymentProperties();
        $paymentType = $properties->getPaymentType();

        $subscriptionTypes = [
            PaymentProperties::PAYMENT_TYPE_DYNAMIC_SUBSCRIPTION,
            PaymentProperties::PAYMENT_TYPE_PREDEFINED_SUBSCRIPTION,
        ];

        if (!\in_array($paymentType, $subscriptionTypes, true) || !$form->isValid() || !$this->hasPaymentFieldDisplayed($form)) {
            return;
        }

        $paymentField = $this->getPaymentField($form);
        $dynamicValues = $this->getDynamicFieldValues($form, $properties);
        $integration = $this->getIntegrationObjectById($properties);

        $token = $paymentField->getValue();
        if (!$token) {
            return;
        }

        $integration->prepareApi();
        $plan = $this->getPlan($form, $integration, $properties, $dynamicValues);
        if (!$plan) {
            $form->addError('Could not create plan');

            return;
        }

        try {
            $subscription = $this->getSubscription($token, $plan, $dynamicValues);
        } catch (\Stripe\Exception\CardException $e) {
            $form->addError(Freeform::t($e->getMessage()));

            $paymentField->setValue('declined: '.$e->getMessage());

            return;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            $paymentField->setValue('declined: '.$e->getMessage());

            return;
        } catch (\Exception $e) {
            $paymentField->setValue('declined: '.$e->getMessage());

            return;
        }

        if ($subscription) {
            $invoice = $this->getInvoice($subscription);

            if (Subscription::STATUS_INCOMPLETE === $subscription->status && Invoice::STATUS_OPEN === $invoice->status) {
                $paymentIntent = $this->getPaymentIntent($invoice);
                if (PaymentIntent::STATUS_REQUIRES_ACTION === $paymentIntent->status) {
                    $form->addAction(new SubscriptionPaymentIntentAction($subscription, $paymentIntent));
                } elseif (PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD === $paymentIntent->status) {
                    if ('card_declined' === $paymentIntent->charges->first()->failure_code) {
                        $paymentField->setValue('declined: '.$paymentIntent->charges->first()->failure_message);

                        return;
                    }
                }
            }

            $paymentField->setValue($subscription->id);
        }
    }

    /**
     * Adds honeypot javascript to forms.
     */
    public function addAttributesToFormTag(AttachFormAttributesEvent $event)
    {
        $form = $event->getForm();

        if ($this->hasPaymentFieldDisplayed($form)) {
            $variables = $this->getStripeVariables($form);

            $event->attachAttribute('data-stripe-payment-field-id', $variables->id);
            $event->attachAttribute('data-stripe-currency-selector', $variables->currencySelector);
            $event->attachAttribute('data-stripe-currency-fixed', $variables->currencyFixed);
            $event->attachAttribute('data-stripe-amount-selector', $variables->amountSelector);
            $event->attachAttribute('data-stripe-amount-fixed', $variables->amountFixed);
            $event->attachAttribute('data-stripe-usage', $variables->usage);
            $event->attachAttribute('data-stripe-public-key', $variables->publicKey);
            $event->attachAttribute('data-stripe', true);
        }
    }

    public function getStripeVariables(Form $form): \stdClass
    {
        $paymentFields = $form->getLayout()->getFields(PaymentInterface::class);
        $integrationId = $form->getPaymentProperties()->getIntegrationId();

        /** @var IntegrationModel $integration */
        $integration = Freeform::getInstance()->paymentGateways->getIntegrationById($integrationId);

        /** @var Stripe $integrationObject */
        $integrationObject = $integration->getIntegrationObject();

        $values = $this->getPaymentFieldJSValues($form);
        $props = $form->getPaymentProperties();
        $isSubscription = PaymentProperties::PAYMENT_TYPE_SINGLE !== $props->getPaymentType();

        if (0 === \count($paymentFields)) {
            return (object) [];
        }

        $paymentField = $paymentFields[0];

        return (object) [
            'id' => $paymentField->getIdAttribute(),
            'currencySelector' => $values['currencySelector'],
            'currencyFixed' => $values['currencyFixed'],
            'amountSelector' => $values['amountSelector'],
            'amountFixed' => $values['amountFixed'],
            'usage' => $isSubscription ? 'reusable' : 'single_use',
            'publicKey' => $integrationObject->getPublicKey(),
        ];
    }

    /**
     * @param Form $form
     *
     * @return array
     */
    private function getPaymentFieldJSValues($form)
    {
        $props = $form->getPaymentProperties();
        $staticAmount = $props->getAmount();
        $staticCurrency = $props->getCurrency();
        $mapping = $props->getPaymentFieldMapping();

        if (!isset($mapping['amount']) && !isset($mapping['currency'])) {
            return [
                'amountSelector' => null,
                'amountFixed' => $staticAmount,
                'currencySelector' => null,
                'currencyFixed' => $staticCurrency,
            ];
        }

        $elementAmount = $elementCurrency = $dynamicAmount = $dynamicCurrency = null;
        // process 3 cases, fixed value, value on same page, value on different page
        $pageFields = $form->getCurrentPage()->getFields();
        foreach ($pageFields as $pageField) {
            if (\in_array($pageField->getType(), self::FIELD_GROUP_TYPES, true)) {
                $selector = "[name={$pageField->getHandle()}]:checked";
            } else {
                $selector = "#{$pageField->getIdAttribute()}";
            }

            if (isset($mapping['amount']) && $mapping['amount'] == $pageField->getHandle()) {
                $elementAmount = $selector;
            }

            if (isset($mapping['currency']) && $mapping['currency'] == $pageField->getHandle()) {
                $elementCurrency = $selector;
            }
        }

        if (isset($mapping['amount'])) {
            $dynamicAmount = $form->get($mapping['amount'])->getValue();
        }

        if (isset($mapping['currency'])) {
            $dynamicCurrency = $form->get($mapping['currency'])->getValue();
        }

        return [
            'amountSelector' => $elementAmount,
            'amountFixed' => $dynamicAmount ?? $staticAmount,
            'currencySelector' => $elementCurrency,
            'currencyFixed' => $dynamicCurrency ?? $staticCurrency,
        ];
    }

    private function hasPaymentFieldDisplayed(Form $form): bool
    {
        return $form->getLayout()->hasFields(PaymentInterface::class);
    }

    private function isFieldOnPage(AbstractField $field, Page $page): bool
    {
        $pageFields = $page->getFields();
        $fieldHandle = $field->getHandle();

        foreach ($pageFields as $pageField) {
            if ($fieldHandle == $pageField->getHandle()) {
                return true;
            }
        }

        return false;
    }

    private function getDynamicFieldValues(Form $form, PaymentProperties $paymentProperties): array
    {
        $dynamicValues = [];

        $paymentFieldMapping = $paymentProperties->getPaymentFieldMapping();
        $customerFieldMapping = $paymentProperties->getCustomerFieldMapping();

        if (\is_array($paymentFieldMapping)) {
            foreach ($paymentFieldMapping as $key => $handle) {
                $field = $form->get($handle);
                if (!$field) {
                    continue;
                }

                $value = $field->getValue();
                if ($value) {
                    $dynamicValues[$key] = $value;
                }
            }
        }

        if (\is_array($customerFieldMapping)) {
            foreach ($customerFieldMapping as $key => $handle) {
                $field = $form->get($handle);
                if (!$field) {
                    continue;
                }

                $value = $field->getValue();
                if ($value) {
                    $dynamicValues[$key] = $value;
                }
            }
        }

        return $dynamicValues;
    }

    /**
     * @return null|Plan
     */
    private function getPlan(Form $form, Stripe $integration, PaymentProperties $properties, array $dynamicValues)
    {
        if (PaymentProperties::PAYMENT_TYPE_DYNAMIC_SUBSCRIPTION === $properties->getPaymentType()) {
            $currency = $dynamicValues[PaymentProperties::FIELD_CURRENCY] ?? $properties->getCurrency();
            $amount = (float) ($dynamicValues[PaymentProperties::FIELD_AMOUNT] ?? $properties->getAmount());
            $interval = $dynamicValues[PaymentProperties::FIELD_INTERVAL] ?? $properties->getInterval();
            $planDetails = new PlanDetails(
                null,
                $amount,
                $currency,
                $interval,
                $form->getName(),
                $form->getHandle()
            );

            $planId = $planDetails->getId();
            $plan = $integration->fetchPlan($planId);

            if (false === $plan) {
                return null;
            }

            if (!$plan) {
                $planId = $integration->createPlan($planDetails);
            }

            if (false === $planId) {
                return null;
            }
        } else {
            $planId = $dynamicValues[PaymentProperties::FIELD_PLAN] ?? $properties->getPlan();
        }

        if ($planId) {
            return Plan::retrieve($planId);
        }

        return null;
    }

    /**
     * @return null|Subscription
     */
    private function getSubscription(string $token, Plan $plan, array $dynamicValues = [])
    {
        $subscription = $customer = null;

        // If we get a card token, we have to create the customer and subscription
        if (0 === strpos($token, 'tok_')) {
            $customerData = CustomerDetails::fromArray($dynamicValues)->toStripeConstructArray();
            $customerData['source'] = $token;

            $customer = Customer::create($customerData);

            $subscription = Subscription::create([
                'customer' => $customer->id,
                'items' => [['plan' => $plan->id]],
                'expand' => ['latest_invoice.payment_intent'],
            ]);
        }

        // If it's a subscription
        if (0 === strpos($token, 'sub_')) {
            $subscription = Subscription::retrieve(
                $token,
                ['expand' => ['latest_invoice.payment_intent']]
            );
        }

        return $subscription;
    }

    private function getInvoice(Subscription $subscription): Invoice
    {
        $invoice = $subscription->latest_invoice;
        if (!$invoice instanceof Invoice) {
            $invoice = Invoice::retrieve($invoice, ['expand' => ['payment_intent']]);
        }

        return $invoice;
    }

    private function getPaymentIntent(Invoice $invoice): PaymentIntent
    {
        $paymentIntent = $invoice->payment_intent;
        if (!$paymentIntent instanceof PaymentIntent) {
            $paymentIntent = PaymentIntent::retrieve($paymentIntent);
        }

        return $paymentIntent;
    }

    /**
     * @throws IntegrationException
     */
    private function getIntegrationObjectById(PaymentProperties $properties): Stripe
    {
        $paymentGatewayHandler = Freeform::getInstance()->paymentGateways;

        $integration = $paymentGatewayHandler->getIntegrationObjectById($properties->getIntegrationId());
        if ($integration instanceof Stripe) {
            return $integration;
        }

        throw new IntegrationException('Could not get integration');
    }

    /**
     * @return CreditCardDetailsField|PaymentInterface
     *
     * @throws IntegrationException
     */
    private function getPaymentField(Form $form): PaymentInterface
    {
        $paymentFields = $form->getLayout()->getFields(PaymentInterface::class);
        $paymentField = reset($paymentFields);

        if (!$paymentField) {
            throw new IntegrationException('Could not find a payment field');
        }

        return $paymentField;
    }
}
