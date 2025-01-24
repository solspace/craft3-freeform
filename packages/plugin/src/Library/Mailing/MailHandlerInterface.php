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

namespace Solspace\Freeform\Library\Mailing;

use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Records\NotificationRecord;

interface MailHandlerInterface
{
    /**
     * Send out an email to recipients using the given mail template.
     *
     * @return int - number of successfully sent emails
     */
    public function sendEmail(
        Form $form,
        array|string $recipients,
        NotificationRecord $notification,
        array $fields,
        Submission $submission = null
    ): int;

    /**
     * @param int $id
     *
     * @return null|NotificationInterface
     */
    public function getNotificationById($id);
}
