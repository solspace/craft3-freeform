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

interface NotificationInterface
{
    public function getHandle(): string;

    public function getFromName(): string;

    public function getFromEmail(): string;

    /**
     * @return null|string
     */
    public function getCc();

    /**
     * @return null|string
     */
    public function getBcc();

    /**
     * @return null|string
     */
    public function getReplyToName();

    /**
     * @return null|string
     */
    public function getReplyToEmail();

    public function isIncludeAttachmentsEnabled(): bool;

    /**
     * @return null|array
     */
    public function getPresetAssets();

    public function getSubject(): string;

    public function getBodyHtml(): string;

    public function getBodyText(): string;
}
