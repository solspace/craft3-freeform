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

namespace Solspace\Freeform\Library\Composer\Components\Fields\Traits;

trait RecipientTrait
{
    /** @var int|string */
    protected $notificationId;

    /**
     * @return null|int|string
     */
    public function getNotificationId()
    {
        return $this->notificationId;
    }

    /**
     * Returns true/false based on if the field should or should not act
     * as a recipient email field and receive emails.
     */
    public function shouldReceiveEmail(): bool
    {
        return (bool) $this->getNotificationId();
    }
}
