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

namespace Solspace\Freeform\Integrations\CRM\HubSpot;

use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseHubSpotIntegration extends CRMIntegration implements HubSpotIntegrationInterface
{
    protected const LOG_CATEGORY = 'HubSpot';

    protected const CATEGORY_DEAL = 'Deal';
    protected const CATEGORY_CONTACT = 'Contact';
    protected const CATEGORY_COMPANY = 'Company';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Access Token',
        instructions: "This is your app's unique ID. You'll need it to make API calls.",
        order: 1,
    )]
    protected ?string $accessToken = null;

    #[Input\Text(
        label: 'IP Address Field',
        instructions: "Enter a custom HubSpot Contact field handle where you wish to store the client's IP address from the submission (optional).",
        order: 2,
    )]
    protected ?string $ipField = null;

    #[Input\Boolean(
        label: 'Append checkbox group field values on Contact update',
        instructions: 'If a Contact already exists in HubSpot, enabling this will append additional checkbox group field values to the Contact inside HubSpot, instead of overwriting the options.',
        order: 3,
    )]
    protected bool $appendContactData = false;

    #[Input\Boolean(
        label: 'Append checkbox group field values on Company update?',
        instructions: 'If a Company already exists in HubSpot, enabling this will append additional checkbox group field values to the Company inside HubSpot, instead of overwriting the options.',
        order: 4,
    )]
    protected bool $appendCompanyData = false;

    public function getAccessToken(): ?string
    {
        return $this->getProcessedValue($this->accessToken);
    }

    public function getIpField(): ?string
    {
        return $this->getProcessedValue($this->ipField);
    }

    public function getAppendContactData(): bool
    {
        return $this->appendContactData;
    }

    public function getAppendCompanyData(): bool
    {
        return $this->appendCompanyData;
    }
}
