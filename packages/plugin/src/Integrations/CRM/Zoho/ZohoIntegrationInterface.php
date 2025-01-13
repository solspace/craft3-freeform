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

namespace Solspace\Freeform\Integrations\CRM\Zoho;

interface ZohoIntegrationInterface
{
    public function getAccountsServer(): ?string;

    public function setAccountsServer(?string $accountsServer): self;

    public function getApiDomain(): ?string;

    public function setApiDomain(?string $apiDomain): self;

    public function getLocation(): ?string;

    public function setLocation(?string $location): self;
}
