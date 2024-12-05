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

namespace Solspace\Freeform\Integrations\EmailMarketing\ConstantContact;

use GuzzleHttp\Client;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegration;

abstract class BaseConstantContactIntegration extends EmailMarketingIntegration implements OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, ConstantContactIntegrationInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'Constant Contact';

    protected const CATEGORY_CONTACT_CUSTOM = 'Contact_Custom';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/contact_lists'));

        $json = json_decode((string) $response->getBody());

        return !empty($json->lists);
    }

    public function fetchFields(ListObject $list, string $category, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/contact_custom_fields'));
        $json = json_decode((string) $response->getBody());

        $fieldList = [];

        $fieldList[] = new FieldObject('first_name', 'First Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('last_name', 'Last Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('job_title', 'Job Title', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('company_name', 'Company Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_number', 'Phone Number', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('anniversary', 'Anniversary', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('birthday_month', 'Birthday Month', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('birthday_day', 'Birthday Day', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('street_address_kind', 'Address: Kind', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('street_address_street', 'Address: Street', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('street_address_city', 'Address: City', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('street_address_state', 'Address: State', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('street_address_postal_code', 'Address: Postal Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('street_address_country', 'Address: Country', FieldObject::TYPE_STRING, $category, false);

        if (!isset($json->custom_fields) || !$json->custom_fields) {
            return $fieldList;
        }

        foreach ($json->custom_fields as $field) {
            $fieldList[] = new FieldObject(
                'custom_'.$field->custom_field_id,
                $field->label,
                FieldObject::TYPE_STRING,
                $category,
                false,
            );
        }

        return $fieldList;
    }

    public function fetchLists(Client $client): array
    {
        $response = $client->get($this->getEndpoint('/contact_lists'));
        $json = json_decode((string) $response->getBody());

        $lists = [];

        if (isset($json->lists)) {
            foreach ($json->lists as $list) {
                if (isset($list->list_id, $list->name)) {
                    $lists[] = new ListObject(
                        $list->list_id,
                        $list->name,
                    );
                }
            }
        }

        return $lists;
    }
}
