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

namespace Solspace\Freeform\Library\DataObjects;

use Solspace\Freeform\Elements\Submission;

class PaymentDetails
{
    /** @var string */
    private $token;

    /** @var Submission */
    private $submission;

    /** @var CustomerDetails */
    private $customer;

    /**
     * PaymentDetails constructor.
     */
    public function __construct(
        string $token,
        Submission $submissionId,
        CustomerDetails $customer
    ) {
        $this->token = $token;
        $this->submission = $submissionId;
        $this->customer = $customer;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getSubmission(): Submission
    {
        return $this->submission;
    }

    /**
     * Get the value of customer.
     *
     * @return CustomerDetails
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
