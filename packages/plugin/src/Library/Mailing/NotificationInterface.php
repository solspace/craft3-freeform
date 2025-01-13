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

    public function getCc(): ?string;

    public function getBcc(): ?string;

    public function getReplyToName(): string;

    public function getReplyToEmail(): ?string;

    public function isIncludeAttachmentsEnabled(): bool;

    public function getPresetAssets(): ?array;

    public function getSubject(): string;

    public function getBodyHtml(): string;

    public function getBodyText(): string;
}
