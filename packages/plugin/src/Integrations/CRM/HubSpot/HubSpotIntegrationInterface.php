<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Integrations\CRM\HubSpot;

interface HubSpotIntegrationInterface
{
    public function getAccessToken(): ?string;

    public function getIpField(): ?string;

    public function getAppendContactData(): bool;

    public function getAppendCompanyData(): bool;
}
