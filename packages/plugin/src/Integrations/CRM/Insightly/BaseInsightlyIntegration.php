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

namespace Solspace\Freeform\Integrations\CRM\Insightly;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseInsightlyIntegration extends CRMIntegration implements InsightlyIntegrationInterface
{
    protected const LOG_CATEGORY = 'Insightly';

    protected const CATEGORY_LEAD = 'Lead';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'API Token',
        instructions: 'Enter your API Token here.',
        order: 1,
    )]
    protected string $apiToken = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'API URL',
        instructions: 'Enter your API specific URL (e.g. "https://api.na1.insightly.com").',
        order: 2,
    )]
    protected string $apiUrl = '';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/Instance'));

        return 200 === $response->getStatusCode();
    }

    public function getApiToken(): string
    {
        return $this->getProcessedValue($this->apiToken);
    }

    public function fetchFields(string $category, Client $client): array
    {
        $fieldList = [];

        $fieldList[] = new FieldObject('SALUTATION', 'Salutation', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('FIRST_NAME', 'First Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('LAST_NAME', 'Last Name', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('TITLE', 'Title', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('EMAIL', 'Email', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('PHONE', 'Phone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('MOBILE', 'Mobile', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('FAX', 'Fax', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('WEBSITE', 'Website', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ORGANISATION_NAME', 'Organization', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('INDUSTRY', 'Industry', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('EMPLOYEE_COUNT', 'Number of Employees', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('IMAGE_URL', 'Image URL', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ADDRESS_STREET', 'Address - Street', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ADDRESS_CITY', 'Address - City', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ADDRESS_STATE', 'Address - State/Province', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ADDRESS_POSTCODE', 'Address - Postal Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('ADDRESS_COUNTRY', 'Address - Country', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('LEAD_ID', 'Lead ID', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('LEAD_DESCRIPTION', 'Lead Description', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('LEAD_RATING', 'Lead Rating', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('LEAD_SOURCE_ID', 'Lead Source', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('LEAD_STATUS_ID', 'Lead Status', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('DATE_CREATED_UTC', 'Lead Created', FieldObject::TYPE_DATETIME, $category, false);
        $fieldList[] = new FieldObject('OWNER_USER_ID', 'Lead Owner', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('RESPONSIBLE_USER_ID', 'User Responsible', FieldObject::TYPE_NUMERIC, $category, false);

        $response = $client->get($this->getEndpoint('/CustomFields/Leads'));

        $json = json_decode($response->getBody(), false);

        foreach ($json as $field) {
            if (!$field->EDITABLE) {
                continue;
            }

            $type = match ($field->FIELD_TYPE) {
                'TEXT', 'DROPDOWN', 'URL', 'MULTILINETEXT' => FieldObject::TYPE_STRING,
                'DATE' => FieldObject::TYPE_DATETIME,
                'BIT' => FieldObject::TYPE_BOOLEAN,
                'NUMERIC' => FieldObject::TYPE_NUMERIC,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                $field->FIELD_NAME,
                $field->FIELD_LABEL,
                $type,
                $category,
                false,
            );
        }

        return $fieldList;
    }

    protected function getApiUrl(): string
    {
        return $this->getProcessedValue($this->apiUrl);
    }
}
