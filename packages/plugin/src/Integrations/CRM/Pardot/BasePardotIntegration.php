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

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2IssuedAtMilliseconds;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2PKCEInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BasePardotIntegration extends CRMIntegration implements OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, OAuth2IssuedAtMilliseconds, OAuth2PKCEInterface, PardotIntegrationInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'Pardot';

    protected const CATEGORY_PROSPECT = 'Prospect';

    protected const CATEGORY_CUSTOM = 'Custom';

    #[Flag(self::FLAG_INTERNAL)]
    #[Input\Hidden]
    protected string $instanceUrl = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'Business Unit ID',
        instructions: 'Enter your Pardot business unit ID here',
        order: 1,
    )]
    protected string $businessUnitId = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Boolean(
        label: 'Use Custom URL?',
        instructions: 'Enable this if you connect to your Salesforce account with a custom company URL (e.g. "mycompany.my.salesforce.com").',
        order: 2,
    )]
    protected bool $useCustomUrl = false;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Custom URL',
        instructions: 'E.g https://mycompany.develop.my.salesforce.com',
        order: 3,
    )]
    protected ?string $customUrl = null;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        instructions: 'Enable this if your Salesforce account is in Sandbox mode (connects to "test.salesforce.com" instead of "login.salesforce.com" or "mycompany.my.salesforce.com").',
        order: 4,
    )]
    protected bool $sandboxMode = false;

    public function checkConnection(Client $client): bool
    {
        $response = $client->get(
            $this->getPardotEndpoint(),
            [
                'query' => [
                    'limit' => 1,
                    'format' => 'json',
                ],
            ],
        );

        $json = json_decode((string) $response->getBody(), true);

        return isset($json['@attributes']) && 'ok' === $json['@attributes']['stat'];
    }

    public function getAuthorizeUrl(): string
    {
        return $this->getDomain().'/services/oauth2/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return $this->getDomain().'/services/oauth2/token';
    }

    public function getInstanceUrl(): string
    {
        return $this->instanceUrl;
    }

    public function setInstanceUrl(string $instanceUrl): self
    {
        $this->instanceUrl = $instanceUrl;

        return $this;
    }

    public function fetchFields(string $category, Client $client): array
    {
        if (self::CATEGORY_PROSPECT === $category) {
            return $this->getProspectFields($category);
        }

        return $this->getCustomFields($client, $category);
    }

    public function getBusinessUnitId(): string
    {
        return $this->getProcessedValue($this->businessUnitId);
    }

    protected function isCustomUrl(): bool
    {
        return $this->useCustomUrl;
    }

    protected function getCustomUrl(): ?string
    {
        return $this->customUrl;
    }

    protected function isSandboxMode(): bool
    {
        return $this->sandboxMode;
    }

    protected function getDomain(): string
    {
        $domain = 'https://login.salesforce.com';

        if ($this->isSandboxMode()) {
            $domain = 'https://test.salesforce.com';
        }

        if ($this->isCustomUrl()) {
            $domain = $this->getCustomUrl();
        }

        return $domain;
    }

    private function getProspectFields(string $category): array
    {
        $fieldList = [];

        $fieldList[] = new FieldObject('salutation', 'Salutation', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('first_name', 'First Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('last_name', 'Last Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email', 'Email', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('password', 'Password', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('company', 'Company', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('prospect_account_id', 'Prospect Account Id', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('website', 'Website', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('job_title', 'Job Title', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('department', 'Department', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('country', 'Country', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('address_one', 'Address One', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('address_two', 'Address Two', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('city', 'City', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('state', 'State', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('territory', 'Territory', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('zip', 'Zip', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone', 'Phone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('fax', 'Fax', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('source', 'Source', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('annual_revenue', 'Annual Revenue', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('employees', 'Employees', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('industry', 'Industry', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('years_in_business', 'Years in Business', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('comments', 'Comments', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('notes', 'Notes', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('score', 'Score', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('is_do_not_email', 'Do not email', FieldObject::TYPE_BOOLEAN, $category, true);
        $fieldList[] = new FieldObject('is_do_not_call', 'Do not call', FieldObject::TYPE_BOOLEAN, $category, true);
        $fieldList[] = new FieldObject('is_reviewed', 'Reviewed', FieldObject::TYPE_BOOLEAN, $category, true);
        $fieldList[] = new FieldObject('is_archived', 'Archived', FieldObject::TYPE_BOOLEAN, $category, true);
        $fieldList[] = new FieldObject('is_starred', 'Starred', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('campaign_id', 'Campaign', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('profile', 'Profile', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('assign_to', 'Assign To', FieldObject::TYPE_STRING, $category, false);

        return $fieldList;
    }

    private function getCustomFields(Client $client, string $category): array
    {
        $response = $client->get($this->getPardotEndpoint('customField'));
        $json = json_decode((string) $response->getBody());

        if (!$json || !isset($json->result)) {
            throw new IntegrationException('Could not fetch fields for Pardot '.$category);
        }

        $fieldList = [];

        foreach ($json->result->customField as $field) {
            if (\is_array($field)) {
                $field = (object) $field;
            }

            if (!\is_object($field) || !isset($field->type)) {
                continue;
            }

            $type = match ($field->data_type) {
                'Text', 'Textarea', 'TextArea', 'Dropdown', 'Radio Button', 'Hidden' => FieldObject::TYPE_STRING,
                'Checkbox', 'Multi-Select' => FieldObject::TYPE_ARRAY,
                'Number' => FieldObject::TYPE_NUMERIC,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                $field->field_id,
                $field->name,
                $type,
                $category,
                false,
            );
        }

        return $fieldList;
    }
}
