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

namespace Solspace\Freeform\Integrations\CRM\Pardot;

use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;

interface PardotIntegrationInterface extends OAuth2ConnectorInterface
{
    public function getInstanceUrl(): string;

    public function setInstanceUrl(string $instanceUrl): self;

    public function getBusinessUnitId(): string;
}
