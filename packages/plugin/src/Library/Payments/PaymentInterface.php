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

interface PaymentInterface
{
    public const TYPE_SINGLE = 'single';
    public const TYPE_SUBSCRIPTION = 'subscription';

    /**
     * Returns type of payment.
     */
    public function getType(): string;
}
