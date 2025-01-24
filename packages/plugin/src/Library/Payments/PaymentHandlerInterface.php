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

namespace Solspace\Freeform\Library\Payments;

interface PaymentHandlerInterface
{
    /**
     * Returns payment for submission, only first payment is returned for subscriptions.
     *
     * @return null|PaymentInterface
     */
    public function getBySubmissionId(int $submissionId);

    /**
     * Finds a payment with a matching resource id for specific integration.
     *
     * @return null|PaymentInterface
     */
    public function getByResourceId(string $resourceId, int $integrationId);

    /**
     * Saves payment.
     */
    public function save(PaymentInterface $payment): bool;
}
