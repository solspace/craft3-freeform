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

namespace Solspace\Freeform\Library\Composer\Components\Properties;

class PaymentProperties extends IntegrationProperties
{
    public const PAYMENT_TYPE_SINGLE = 'single';
    public const PAYMENT_TYPE_PREDEFINED_SUBSCRIPTION = 'predefined_subscription';
    public const PAYMENT_TYPE_DYNAMIC_SUBSCRIPTION = 'dynamic_subscription';

    public const PLAN_INTERVAL_DAILY = 'daily';
    public const PLAN_INTERVAL_WEEKLY = 'weekly';
    public const PLAN_INTERVAL_BIWEEKLY = 'biweekly';
    public const PLAN_INTERVAL_MONTHLY = 'monthly';
    public const PLAN_INTERVAL_ANNUALLY = 'annually';

    public const NOTIFICATION_TYPE_CHARGE_SUCCEEDED = 'charge_success';
    public const NOTIFICATION_TYPE_CHARGE_FAILED = 'charge_failed';
    public const NOTIFICATION_TYPE_SUBSCRIPTION_CREATED = 'subscription_created';
    public const NOTIFICATION_TYPE_SUBSCRIPTION_ENDED = 'subscription_ended';
    public const NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_SUCCEEDED = 'subscription_payment_succeeded';
    public const NOTIFICATION_TYPE_SUBSCRIPTION_PAYMENT_FAILED = 'subscription_payment_failed';

    public const FIELD_PAYMENT_TYPE = 'paymentType';
    public const FIELD_AMOUNT = 'amount';
    public const FIELD_CURRENCY = 'currency';
    public const FIELD_PLAN = 'plan';
    public const FIELD_INTERVAL = 'interval';
    public const FIELD_DESCRIPTION = 'description';
    public const FIELD_PAYMENT_NOTIFICATIONS = 'paymentNotifications';
    public const FIELD_PAYMENT_FIELD_MAPPING = 'paymentFieldMapping';
    public const FIELD_CUSTOMER_FIELD_MAPPING = 'customerFieldMapping';

    /** @var string */
    protected $paymentType;

    /** @var float */
    protected $amount;

    /** @var string */
    protected $currency = 'USD';

    /** @var string */
    protected $interval = self::PLAN_INTERVAL_MONTHLY;

    /** @var string */
    protected $description;

    /** @var string */
    protected $plan = '';

    /** @var array */
    protected $paymentNotifications = [];

    /** @var array */
    protected $paymentFieldMapping;

    /** @var array */
    protected $customerFieldMapping;

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @return string
     */
    public function getPlan()
    {
        return $this->plan;
    }

    /**
     * @return string
     */
    public function getPaymentType()
    {
        return $this->paymentType;
    }

    /**
     * @return string
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    public function getPaymentNotifications(): array
    {
        return $this->paymentNotifications;
    }

    /**
     * @return array
     */
    public function getPaymentFieldMapping()
    {
        return $this->paymentFieldMapping;
    }

    /**
     * @return array
     */
    public function getCustomerFieldMapping()
    {
        return $this->customerFieldMapping;
    }

    /**
     * {@inheritDoc}
     */
    protected function getPropertyManifest(): array
    {
        $props = parent::getPropertyManifest();

        $props[self::FIELD_CURRENCY] = self::TYPE_STRING;
        $props[self::FIELD_AMOUNT] = self::TYPE_DOUBLE;
        $props[self::FIELD_PLAN] = self::TYPE_STRING;
        $props[self::FIELD_PAYMENT_TYPE] = self::TYPE_STRING;
        $props[self::FIELD_INTERVAL] = self::TYPE_STRING;
        $props[self::FIELD_DESCRIPTION] = self::TYPE_STRING;
        $props[self::FIELD_PAYMENT_NOTIFICATIONS] = self::TYPE_ARRAY;
        $props[self::FIELD_PAYMENT_FIELD_MAPPING] = self::TYPE_ARRAY;
        $props[self::FIELD_CUSTOMER_FIELD_MAPPING] = self::TYPE_ARRAY;

        return $props;
    }
}
