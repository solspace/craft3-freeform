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

class SubscriptionDetails
{
    /** @var string */
    private $token;

    /** @var Submission */
    private $submission;

    /**
     * PaymentDetails constructor.
     */
    public function __construct(string $token, Submission $submission)
    {
        $this->token = $token;
        $this->submission = $submission;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSubmission(): Submission
    {
        return $this->submission;
    }
}
