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

namespace Solspace\Freeform\Integrations\CRM\Keap;

use GuzzleHttp\Client;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseKeapIntegration extends CRMIntegration implements OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, KeapIntegrationInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'Keap';

    protected const CATEGORY_CONTACT = 'Contact';

    protected const CATEGORY_TAG = 'Tag';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/businessProfile'));

        return 200 === $response->getStatusCode();
    }

    public function getAuthorizeUrl(): string
    {
        return 'https://accounts.infusionsoft.com/app/oauth/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://api.infusionsoft.com/token';
    }

    public function fetchFields(string $category, Client $client): array
    {
        if (self::CATEGORY_TAG === $category) {
            return [
                new FieldObject(
                    'tags',
                    'Tags',
                    FieldObject::TYPE_STRING,
                    $category,
                    false,
                ),
            ];
        }

        // Create Contact fields
        $response = $client->get($this->getEndpoint('/contacts/model'));
        $json = json_decode((string) $response->getBody(), false);

        if (!$json || !isset($json->custom_fields)) {
            throw new IntegrationException('Could not fetch fields for Keap '.$category);
        }

        $fieldList = [];
        $fieldList[] = new FieldObject('default:given_name', 'First Name', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('default:middle_name', 'Middle Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:family_name', 'Last Name', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('default:prefix', 'Title', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:suffix', 'Suffix', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:preferred_name', 'Nickname', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:birth_date', 'Birthday', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:company', 'Company', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:job_title', 'Job Title', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email_addresses:EMAIL1:email', 'Email Address 1', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email_addresses:EMAIL2:email', 'Email Address 2', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email_addresses:EMAIL3:email', 'Email Address 3', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:website', 'Website', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:TWITTER:name', 'X', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:FACEBOOK:name', 'Facebook', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:LINKED_IN:name', 'LinkedIn', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:INSTAGRAM:name', 'Instagram', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:SNAPCHAT:name', 'Snapchat', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:YOUTUBE:name', 'YouTube', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('social_accounts:PINTEREST:name', 'Pinterest', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_numbers:PHONE1:number', 'Phone Number 1', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_numbers:PHONE2:number', 'Phone Number 2', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_numbers:PHONE3:number', 'Phone Number 3', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_numbers:PHONE4:number', 'Phone Number 4', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone_numbers:PHONE5:number', 'Phone Number 5', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('fax_numbers:FAX1:number', 'Fax Number 1', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('fax_numbers:FAX2:number', 'Fax Number 2', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:line1', 'Billing Address: Line 1', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:line2', 'Billing Address: Line 2', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:locality', 'Billing Address: Locality', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:region_code', 'Billing Address: Region Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:postal_code', 'Billing Address: Postal Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:zip_code', 'Billing Address: Zip Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('addresses:BILLING:country_code', 'Billing Address: Country Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:spouse_name', 'Spouse Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:preferred_locale', 'Timezone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:anniversary_date', 'Anniversary Date', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:contact_type', 'Contact Type', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:source_type', 'Source Type', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:referral_code', 'Referral Code', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:time_zone', 'Timezone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('default:leadsource_id', 'Lead Source ID', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('default:owner_id', 'Owner ID', FieldObject::TYPE_NUMERIC, $category, false);

        foreach ($json->custom_fields as $field) {
            $type = match ($field->field_type) {
                'TEXT', 'TEXT_AREA', 'RADIO', 'PERCENT PHONE_NUMBER', 'NAME', 'MONTH', 'EMAIL', 'STATE', 'DRILLDOWN', 'WEBSITE', 'DROPDOWN', 'USER', 'YEAR', 'YES_NO', 'DAY_OF_WEEK', 'SOCIAL_SECURITY_NUMBER' => FieldObject::TYPE_STRING,
                'LIST_BOX', 'USER_LIST_BOX' => FieldObject::TYPE_ARRAY,
                'WHOLE_NUMBER', 'CURRENCY' => FieldObject::TYPE_NUMERIC,
                'DECIMAL_NUMBER' => FieldObject::TYPE_FLOAT,
                'DATE_TIME' => FieldObject::TYPE_DATETIME,
                'DATE' => FieldObject::TYPE_DATE,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                'custom_field:'.$field->id,
                $field->label,
                $type,
                $category,
                false,
            );
        }

        return $fieldList;
    }

    protected function getApiUrl(): string
    {
        return 'https://api.infusionsoft.com/crm/rest';
    }
}
