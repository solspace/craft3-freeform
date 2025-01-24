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

namespace Solspace\Freeform\Library\Database;

use Solspace\Freeform\Elements\SpamSubmission;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Form;

interface SubmissionHandlerInterface
{
    /**
     * Stores the submitted fields to database.
     */
    public function storeSubmission(Form $form, Submission $submission): bool;

    /**
     * Add a session flash variable that the form has been submitted.
     */
    public function markFormAsSubmitted(Form $form);

    /**
     * Check for a session flash variable for form submissions.
     */
    public function wasFormFlashSubmitted(Form $form): bool;

    /**
     * Creates non-stored Submission or SpamSubmission instance from form field values.
     *
     * @return SpamSubmission|Submission
     */
    public function createSubmissionFromForm(Form $form);

    /**
     * Runs all integrations on submission.
     *
     * @param AbstractField[] $mailingListOptedInFields
     */
    public function postProcessSubmission(Form $form, Submission $submission, array $mailingListOptedInFields);
}
