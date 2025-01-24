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

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Fields\CheckboxGroupField;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationStorageInterface;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class HubSpotV1 extends AbstractCRMIntegration
{
    public const SETTING_API_KEY = 'api_key';
    public const SETTING_IP_FIELD = 'ip_field';
    public const SETTING_APPEND_COMPANY_DATA = 'append_company_data';
    public const SETTING_APPEND_CONTACT_DATA = 'append_contact_data';

    public const TITLE = 'HubSpot (v1)';
    public const LOG_CATEGORY = 'HubSpot';

    /**
     * Returns a list of additional settings for this integration
     * Could be used for anything, like - AccessTokens.
     *
     * @return SettingBlueprint[]
     */
    public static function getSettingBlueprints(): array
    {
        return [
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_API_KEY,
                'Private App Token',
                'Enter your HubSpot Private App token here.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_IP_FIELD,
                'IP Address Field',
                'Enter a custom HubSpot Contact field handle where you wish to store the client\'s IP address from the submission (optional).'
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_APPEND_CONTACT_DATA,
                'Append checkbox group field values on Contact update?',
                'If a Contact already exists in HubSpot, enabling this will append additional checkbox group field values to the Contact inside HubSpot, instead of overwriting the options.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_APPEND_COMPANY_DATA,
                'Append checkbox group field values on Company update?',
                'If a Company already exists in HubSpot, enabling this will append additional checkbox group field values to the Company inside HubSpot, instead of overwriting the options.',
                false
            ),
        ];
    }

    /**
     * Push objects to the CRM.
     *
     * @param array $formFields
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $isAppendContactData = $this->getSetting(self::SETTING_APPEND_CONTACT_DATA);
        $isAppendCompanyData = $this->getSetting(self::SETTING_APPEND_COMPANY_DATA);

        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/deals/v1/deal/');

        $dealProps = [];
        $contactProps = [];
        $companyProps = [];
        $appendContactFields = [];
        $appendCompanyFields = [];

        foreach ($keyValueList as $key => $value) {
            preg_match('/^(\w+)___(.+)$/', $key, $matches);

            [$all, $target, $propName] = $matches;

            $value = $this->formatValue($value, $formFields[$key]);

            switch ($target) {
                case 'contact':
                    $contactProps[] = ['value' => $value, 'property' => $propName];

                    // Checks which contact's values we'll need to append to an existing HS values based on a form field type
                    if ($isAppendContactData) {
                        if (isset($formFields[$key])) {
                            if ($this->isAppendFieldType($formFields[$key])) {
                                $appendContactFields[] = $propName;
                            }
                        }
                    }

                    break;

                case 'company':
                    $companyProps[] = ['value' => $value, 'name' => $propName];

                    // Checks which company's values we'll need to append to an existing HS values based on a form field type
                    if ($isAppendCompanyData) {
                        if (isset($formFields[$key])) {
                            if ($this->isAppendFieldType($formFields[$key])) {
                                $appendCompanyFields[] = $propName;
                            }
                        }
                    }

                    break;

                case 'deal':
                    $dealProps[] = ['value' => $value, 'name' => $propName];

                    break;
            }
        }

        $companyId = null;
        $companyName = null;

        // Gets posted email address

        $contactEmail = $this->getEmailFieldValue($contactProps);

        if ($companyProps) {
            try {
                // Gets posted company domain address
                $companyDomain = $this->getDomainFieldValue($companyProps);

                if (!$companyDomain && $contactEmail) {
                    $companyDomain = $this->extractDomainFromEmail($contactEmail);

                    if ($companyDomain) {
                        $companyProps = $this->addCompanyDomainToCompanyProps($companyDomain, $companyProps);
                    }
                }

                // Queries for a Company record based on the domain
                if ($companyDomain) {
                    $queryProperties = ['domain'];

                    // We have to query appendable fields so later we can update our company props
                    if ($appendCompanyFields) {
                        $queryProperties = array_merge($queryProperties, $appendCompanyFields);
                    }

                    $response = $this->getCompanyByDomain($companyDomain, $client, $queryProperties);

                    $json = json_decode((string) $response->getBody());

                    // If we've found a company based on the domain name
                    if (\count($json->results) > 0) {
                        $company = $json->results[0];

                        if (isset($company->companyId)) {
                            $companyId = $company->companyId;
                        }

                        // We'll append appendable values
                        if ($appendCompanyFields && isset($company->properties)) {
                            $companyProps = $this->appendValuesToCompanyProperties(
                                $companyProps,
                                $appendCompanyFields,
                                $company
                            );
                        }
                    }
                }

                // If we found a company based on the domain
                if ($companyId) {
                    // We'll update it
                    $response = $this->updateCompanyById($companyId, $client, $companyProps);
                } else {
                    // Or we'll create a new company
                    $response = $this->createCompany($client, $companyProps);
                }

                $json = json_decode((string) $response->getBody());
                if (isset($json->companyId)) {
                    $companyId = $json->companyId;
                }

                if (isset($json->properties->name->value)) {
                    $companyName = $json->properties->name->value;
                }

                $this->getHandler()->onAfterResponse($this, $response);
            } catch (RequestException $e) {
                $responseBody = (string) $e->getResponse()->getBody();

                $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

        $contactId = null;

        if ($contactProps) {
            if ($this->getSetting(self::SETTING_IP_FIELD) && isset($_SERVER['REMOTE_ADDR'])) {
                $contactProps[] = [
                    'value' => $_SERVER['REMOTE_ADDR'],
                    'property' => $this->getSetting(self::SETTING_IP_FIELD),
                ];
            }

            try {
                // If we have an email value posted through the form
                if ($contactEmail) {
                    // We'll query for an existing contact by email
                    $response = $this->getContactByEmail($contactEmail, $client, $appendContactFields);

                    $json = json_decode((string) $response->getBody());
                    if (isset($json->vid)) {
                        $contactId = $json->vid;
                    }
                }

                if ($companyName) {
                    $contactProps = $this->addValueToContactProps('company', $companyName, $contactProps);
                }

                // If there is a record with the posted email
                if ($contactId) {
                    // We'll update mapped contact values with appendable values
                    if ($appendContactFields) {
                        if (isset($json->properties)) {
                            $contactProps = $this->appendValuesToContactProperties(
                                $contactProps,
                                $appendContactFields,
                                $json
                            );
                        }
                    }

                    // We'll update contact by email
                    $response = $this->updateContactByEmail($contactEmail, $client, $contactProps);
                } else {
                    // Or we'll create a new contact
                    $response = $this->createContact($client, $contactProps);
                }

                $json = json_decode((string) $response->getBody());
                if (isset($json->vid)) {
                    $contactId = $json->vid;
                }

                $this->getHandler()->onAfterResponse($this, $response);
            } catch (RequestException $e) {
                if ($e->getResponse()) {
                    $json = json_decode((string) $e->getResponse()->getBody());

                    if (isset($json->status, $json->message)) {
                        // If finding a contact by email was unsuccessful it's defined as an error so we'll create a new contact then
                        if ('error' === $json->status && 'contact does not exist' === $json->message) {
                            $response = $this->createContact($client, $contactProps);

                            $json = json_decode((string) $response->getBody());
                            if (isset($json->vid)) {
                                $contactId = $json->vid;
                            }

                            $this->getHandler()->onAfterResponse($this, $response);
                        } else {
                            $responseBody = (string) $e->getResponse()->getBody();

                            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
                        }
                    } elseif (isset($json->error, $json->identityProfile) && 'CONTACT_EXISTS' === $json->error) {
                        $contactId = $json->identityProfile->vid;
                    } else {
                        $responseBody = (string) $e->getResponse()->getBody();

                        $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
                    }
                }
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

        if (!empty($dealProps)) {
            $deal = [
                'properties' => $dealProps,
            ];

            if ($companyId || $contactId) {
                $deal['associations'] = [];

                if ($companyId) {
                    $deal['associations']['associatedCompanyIds'] = [$companyId];
                }

                if ($contactId) {
                    $deal['associations']['associatedVids'] = [$contactId];
                }
            }

            $response = $client->post(
                $endpoint,
                ['json' => $deal]
            );

            $this->getHandler()->onAfterResponse($this, $response);

            return 200 === $response->getStatusCode();
        }

        return true;
    }

    /**
     * Check if it's possible to connect to the API.
     */
    public function checkConnection(): bool
    {
        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/account-info/v3/details');

        try {
            $response = $client->get($endpoint);
            $json = json_decode((string) $response->getBody(), true);

            return isset($json['portalId']);
        } catch (RequestException $exception) {
            throw new IntegrationException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        $fieldList = [];
        $this->extractCustomFields(
            '/properties/v1/deals/properties/',
            'deal',
            $fieldList
        );

        $this->extractCustomFields(
            '/properties/v1/contacts/properties/',
            'contact',
            $fieldList
        );

        $this->extractCustomFields(
            '/properties/v1/companies/properties/',
            'company',
            $fieldList
        );

        return $fieldList;
    }

    /**
     * Authorizes the application
     * Returns the access_token.
     *
     * @throws IntegrationException
     */
    public function fetchAccessToken(): string
    {
        return $this->getSetting(self::SETTING_API_KEY);
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
    }

    /**
     * Perform anything necessary before this integration is saved.
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
        $model->updateAccessToken($this->getSetting(self::SETTING_API_KEY));
    }

    protected function getApiRootUrl(): string
    {
        return 'https://api.hubapi.com/';
    }

    private function extractCustomFields(string $endpoint, string $dataType, array &$fieldList)
    {
        $client = $this->generateAuthorizedClient();
        $response = $client->get($this->getEndpoint($endpoint));

        $data = json_decode((string) $response->getBody());

        foreach ($data as $field) {
            if ($field->readOnlyValue || $field->hidden || $field->calculated) {
                continue;
            }

            $type = null;

            switch ($field->type) {
                case 'string':
                case 'enumeration':
                case 'phone_number':
                    $type = FieldObject::TYPE_STRING;

                    break;

                case 'datetime':
                case 'date':
                    $type = FieldObject::TYPE_MICROTIME;

                    break;

                case 'bool':
                    $type = FieldObject::TYPE_BOOLEAN;

                    break;

                case 'number':
                    $type = FieldObject::TYPE_NUMERIC;

                    break;
            }

            if (null === $type) {
                continue;
            }

            $dataLabel = ucfirst($dataType);
            $fieldObject = new FieldObject(
                $dataType.'___'.$field->name,
                $field->label." ({$dataLabel})",
                $type,
                false
            );

            $fieldList[] = $fieldObject;
        }
    }

    /**
     * Get posted email value.
     *
     * @param mixed $contactProps
     *
     * @return string
     */
    private function getEmailFieldValue($contactProps)
    {
        foreach ($contactProps as $contactProp) {
            if (isset($contactProp['property'])) {
                if ('email' === $contactProp['property']) {
                    if (isset($contactProp['value'])) {
                        return $contactProp['value'];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get's posted domain value.
     *
     * @param mixed $companyProps
     *
     * @return string
     */
    private function getDomainFieldValue($companyProps)
    {
        foreach ($companyProps as $companyProp) {
            if (isset($companyProp['name'])) {
                if ('domain' === $companyProp['name']) {
                    if (isset($companyProp['value'])) {
                        return $companyProp['value'];
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if Form field's type calls for a value append.
     *
     * @param mixed $formField
     *
     * @return bool
     */
    private function isAppendFieldType($formField)
    {
        if ($formField instanceof CheckboxGroupField) {
            return true;
        }

        return false;
    }

    /**
     * Queries for a contact based on email.
     *
     * @param mixed $email
     * @param mixed $client
     * @param mixed $contactProps
     *
     * @return mixed
     */
    private function getContactByEmail($email, $client, $contactProps)
    {
        return $client->get(
            $this->getEndpoint('/contacts/v1/contact/email/'.$email.'/profile'),
            ['json' => ['properties' => $contactProps]]
        );
    }

    /**
     * Updates HS Contact based on email.
     *
     * @param mixed $email
     * @param mixed $client
     * @param mixed $contactProps
     *
     * @return mixed
     */
    private function updateContactByEmail($email, $client, $contactProps)
    {
        return $client->post(
            $this->getEndpoint('/contacts/v1/contact/email/'.$email.'/profile'),
            ['json' => ['properties' => $contactProps]]
        );
    }

    /**
     * Queries for a HS Company based on a domain.
     *
     * @param mixed $companyDomain
     * @param mixed $client
     * @param mixed $queryProperties
     *
     * @return mixed
     */
    private function getCompanyByDomain($companyDomain, $client, $queryProperties)
    {
        return $client->post(
            $this->getEndpoint('companies/v2/domains/'.$companyDomain.'/companies'),
            [
                'json' => [
                    'limit' => 1,
                    'requestOptions' => [
                        'properties' => $queryProperties,
                    ],
                    'offset' => [
                        'isPrimary' => true,
                        'companyId' => 0,
                    ],
                ],
            ]
        );
    }

    /**
     * Updates HS Company based on company id.
     *
     * @param mixed $companyId
     * @param mixed $client
     * @param mixed $companyProps
     *
     * @return mixed
     */
    private function updateCompanyById($companyId, $client, $companyProps)
    {
        return $client->put(
            $this->getEndpoint('companies/v2/companies/'.$companyId),
            ['json' => ['properties' => $companyProps]]
        );
    }

    /**
     * Creates a new HS Company.
     *
     * @param mixed $client
     * @param mixed $companyProps
     *
     * @return mixed
     */
    private function createCompany($client, $companyProps)
    {
        return $client->post(
            $this->getEndpoint('companies/v2/companies'),
            ['json' => ['properties' => $companyProps]]
        );
    }

    /**
     * Creates a new HS contact.
     *
     * @param mixed $client
     * @param mixed $contactProps
     *
     * @return mixed
     */
    private function createContact($client, $contactProps)
    {
        return $client->post(
            $this->getEndpoint('/contacts/v1/contact'),
            ['json' => ['properties' => $contactProps]]
        );
    }

    /**
     * Appends appendable newly posted values to the current company's values.
     *
     * @param mixed $companyProps
     * @param mixed $appendCompanyFields
     * @param mixed $company
     *
     * @return mixed
     */
    private function appendValuesToCompanyProperties($companyProps, $appendCompanyFields, $company)
    {
        foreach ($companyProps as $key => $companyProp) {
            $companyPropValue = $companyProp['value'];
            $companyPropName = $companyProp['name'];

            if (\in_array($companyPropName, $appendCompanyFields)) {
                if (isset($company->properties->{$companyPropName}->value) && $company->properties->{$companyPropName}->value) {
                    if ($companyPropValue) {
                        $newCompanyPropValue = $company->properties->{$companyPropName}->value.';'.$companyPropValue;
                    } else {
                        $newCompanyPropValue = $company->properties->{$companyPropName}->value;
                    }

                    // Clean up duplicate values
                    $valueArray = explode(';', $newCompanyPropValue);
                    $valueArray = array_unique($valueArray);
                    $newCompanyPropValue = implode(';', $valueArray);

                    $companyProps[$key] = [
                        'value' => $newCompanyPropValue,
                        'name' => $companyPropName,
                    ];
                }
            }
        }

        return $companyProps;
    }

    /**
     * Appends appendable newly posted values to the current contact's values.
     *
     * @param mixed $contactProps
     * @param mixed $appendContactFields
     * @param mixed $contact
     *
     * @return mixed
     */
    private function appendValuesToContactProperties($contactProps, $appendContactFields, $contact)
    {
        foreach ($contactProps as $key => $contactProp) {
            $contactPropValue = $contactProp['value'];
            $contactPropName = $contactProp['property'];

            // We are appending appendable values based on newly posted values and current record values
            if (\in_array($contactPropName, $appendContactFields)) {
                if (isset($contact->properties->{$contactPropName}->value) && $contact->properties->{$contactPropName}->value) {
                    if ($contactPropValue) {
                        $newCompanyPropValue = $contact->properties->{$contactPropName}->value.';'.$contactPropValue;
                    } else {
                        $newCompanyPropValue = $contact->properties->{$contactPropName}->value;
                    }

                    // Clean up duplicate values
                    $valueArray = explode(';', $newCompanyPropValue);
                    $valueArray = array_unique($valueArray);
                    $newCompanyPropValue = implode(';', $valueArray);

                    $contactProps[$key] = [
                        'value' => $newCompanyPropValue,
                        'property' => $contactPropName,
                    ];
                }
            }
        }

        return $contactProps;
    }

    private function extractDomainFromEmail($email)
    {
        if (preg_match('/^.*@([^@]+)$$/', $email, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function addCompanyDomainToCompanyProps($companyDomain, $companyProps)
    {
        foreach ($companyProps as $key => $companyProp) {
            $companyPropName = $companyProp['name'];

            if ('domain' == $companyPropName) {
                $companyProps[$key] = [
                    'value' => $companyDomain,
                    'name' => $companyPropName,
                ];

                return $companyProps;
            }
        }

        $companyProps[] = [
            'value' => $companyDomain,
            'name' => 'domain',
        ];

        return $companyProps;
    }

    private function addValueToContactProps($searchPropName, $value, $contactProps)
    {
        foreach ($contactProps as $key => $contactProp) {
            $propName = $contactProp['property'];

            if ($propName == $searchPropName) {
                $contactProps[$key] = [
                    'value' => $value,
                    'property' => $searchPropName,
                ];

                return $contactProps;
            }
        }

        $contactProps[] = [
            'value' => $value,
            'property' => $searchPropName,
        ];

        return $contactProps;
    }

    private function formatValue($value, $formField)
    {
        $isMultiCheckbox = $this->isAppendFieldType($formField);

        if ($isMultiCheckbox) {
            $value = str_replace(', ', ';', $value);
        }

        return $value;
    }

    private function generateAuthorizedClient(): Client
    {
        return new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ],
        ]);
    }
}
