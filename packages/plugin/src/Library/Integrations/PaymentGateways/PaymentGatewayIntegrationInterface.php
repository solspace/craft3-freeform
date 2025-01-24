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

namespace Solspace\Freeform\Library\Integrations\PaymentGateways;

use Solspace\Freeform\Library\Composer\Components\Properties\PaymentProperties;
use Solspace\Freeform\Library\DataObjects\PaymentDetails;
use Solspace\Freeform\Library\DataObjects\PlanDetails;
use Solspace\Freeform\Library\DataObjects\SubscriptionDetails;
use Solspace\Freeform\Models\Pro\Payments\SubscriptionPlanModel;

interface PaymentGatewayIntegrationInterface
{
    /**
     * @return mixed
     */
    public function processPayment(PaymentDetails $paymentDetails, PaymentProperties $paymentProperties);

    /**
     * @return mixed
     */
    public function processSubscription(SubscriptionDetails $subscriptionDetails, PaymentProperties $paymentProperties);

    /**
     * @return SubscriptionPlanModel[]
     */
    public function fetchPlans();

    /**
     * @return null|SubscriptionPlanModel
     */
    public function fetchPlan(string $id);

    /**
     * @return false|string
     */
    public function createPlan(PlanDetails $planDetails);
}
