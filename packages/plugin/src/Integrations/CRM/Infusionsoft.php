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

namespace Solspace\Freeform\Integrations\CRM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Exceptions\Integrations\CRMIntegrationNotFoundException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\CRM\CRMOAuthConnector;
use Solspace\Freeform\Library\Integrations\CRM\RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class Infusionsoft extends CRMOAuthConnector implements RefreshTokenInterface
{
    public const TITLE = 'Infusionsoft';
    public const LOG_CATEGORY = 'Infusionsoft';

    public const SETTING_REFRESH_TOKEN = 'refresh_token';

    protected static $_REFRESHED_TOKENS = [];

    public static function getSettingBlueprints(): array
    {
        $defaults = parent::getSettingBlueprints();

        // Add the refresh token
        $defaults[] = new SettingBlueprint(
            SettingBlueprint::TYPE_INTERNAL,
            self::SETTING_REFRESH_TOKEN,
            'Refresh Token',
            'You should not set this',
            false
        );

        return $defaults;
    }

    /**
     * Push objects to the CRM.
     *
     * @param null|mixed $formFields
     *
     * @throws \Exception
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        // This should automatically refresh the access token if needed
        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/contacts');

        // Build out the array of field values
        $keyValueList = array_filter($keyValueList);
        $keyValueList = $this->processFields($keyValueList);

        // Tack on our specific attributes - duplicate_option can be either Email or EmailAndName. This could be made
        // into a setting on the integration
        // https://developer.infusionsoft.com/docs/rest/#!/Contact/createOrUpdateContactUsingPUT
        $keyValueList['duplicate_option'] = 'Email';

        // Assume all source types are WEBFORM
        $keyValueList['source_type'] = 'WEBFORM';

        // $this->getLogger()->info('Submitting to Infusionsoft', $keyValueList);

        $applyTags = [];
        if (isset($keyValueList['infusionsoftTagId'])) {
            $applyTags = explode(',', $keyValueList['infusionsoftTagId']);
            unset($keyValueList['infusionsoftTagId']);
        }

        try {
            $response = $client->put($endpoint, ['json' => $keyValueList]);
            $this->getHandler()->onAfterResponse($this, $response);
            if (\count($applyTags) > 0) {
                $responseBody = json_decode((string) $response->getBody(), true);
                $endpoint = $this->getEndpoint('/contacts/'.$responseBody['id'].'/tags');
                $response = $client->post(
                    $endpoint,
                    [
                        'json' => ['tagIds' => $applyTags],
                    ]
                );
            }

            return 201 === $response->getStatusCode();
        } catch (RequestException $e) {
            $exceptionResponse = $e->getResponse();
            if (!$exceptionResponse) {
                $this->getLogger()->error($e->getMessage(), ['exception' => $e->getMessage()]);

                throw $e;
            }

            $responseBody = (string) $exceptionResponse->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            if (400 === $exceptionResponse->getStatusCode()) {
                $errors = json_decode($exceptionResponse->getBody(), false);

                if (\is_array($errors)) {
                    foreach ($errors as $error) {
                        if ('REQUIRED_FIELD_MISSING' === strtoupper($error->errorCode)) {
                            return false;
                        }
                    }
                }
            }

            throw $e;
        }
    }

    /**
     * Check if it's possible to connect to the API.
     */
    public function checkConnection(): bool
    {
        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/account/profile');

        $response = $client->get($endpoint);

        $json = json_decode($response->getBody(), true);

        return !empty($json);
    }

    /**
     * Fetch the custom fields from Infusionsoft.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        try {
            // This will also refresh the token if its expired, hence the try-catch
            $client = $this->generateAuthorizedClient();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());

            return [];
        }

        try {
            $response = $client->get($this->getEndpoint('/contacts/model'));
        } catch (RequestException $e) {
            $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);

            return [];
        }

        $data = json_decode($response->getBody(), false);

        $fieldList = self::getDefaultFields();

        foreach ($data->custom_fields as $field) {
            $type = null;

            switch ($field->field_type) {
                case 'Text':
                case 'TextArea':
                case 'Radio':
                case 'Dropdown':
                case 'YesNo':
                    $type = FieldObject::TYPE_STRING;

                    break;

                case 'ListBox':
                    $type = FieldObject::TYPE_ARRAY;

                    break;

                case 'Number':
                case 'WholeNumber':
                case 'Currency':
                    $type = FieldObject::TYPE_NUMERIC;

                    break;

                case 'Date':
                    $type = FieldObject::TYPE_DATE;

                    break;

                case 'DateTime':
                    $type = FieldObject::TYPE_DATETIME;

                    break;
            }

            if (null === $type) {
                continue;
            }

            $fieldObject = new FieldObject(
                'custom:'.$field->id,
                $field->label,
                $type
            );

            $fieldList[] = $fieldObject;
        }

        return $fieldList;
    }

    /**
     * @throws CRMIntegrationNotFoundException
     * @throws IntegrationException
     * @throws \ReflectionException
     */
    public function refreshToken(): string
    {
        // Prevent this method from being called more than once in a request
        if (isset(self::$_REFRESHED_TOKENS[$this->getId()])) {
            return $this->getAccessToken();
        }

        $client = new Client([
            'headers' => [
                'Authorization' => 'Basic '.base64_encode($this->getClientId().':'.$this->getClientSecret()),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
        ]);

        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getSetting(self::SETTING_REFRESH_TOKEN),
        ];

        try {
            $response = $client->post(
                $this->getAccessTokenUrl(),
                ['form_params' => $payload]
            );
            self::$_REFRESHED_TOKENS[$this->getId()] = true;
        } catch (RequestException $e) {
            throw new IntegrationException((string) $e->getResponse()->getBody());
        }

        $json = json_decode($response->getBody(), false);

        if (!isset($json->access_token)) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    "No 'access_token' present in auth response for {serviceProvider}",
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        if (!isset($json->refresh_token)) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    "No 'refresh_token' present in auth response for {serviceProvider}.",
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        $this->setAccessToken($json->access_token);
        $this->onAfterFetchAccessToken($json);

        try {
            Freeform::getInstance()->crm->updateAccessToken($this);
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to save refreshed token', [$e->getMessage()]);
        }

        return $this->getAccessToken();
    }

    /**
     * @return array|bool|string
     */
    public function convertCustomFieldValue(FieldObject $fieldObject, AbstractField $field)
    {
        $value = parent::convertCustomFieldValue($fieldObject, $field);

        if (FieldObject::TYPE_ARRAY === $fieldObject->getType()) {
            $value = \is_array($value) ? implode(';', $value) : $value;
        }

        return $value;
    }

    protected function onAfterFetchAccessToken(\stdClass $responseData)
    {
        // Make sure we set our refresh token!
        if (isset($responseData->refresh_token)) {
            $this->setSetting(self::SETTING_REFRESH_TOKEN, $responseData->refresh_token);
        }
        $this->setAccessTokenUpdated(true);
    }

    /**
     * URL pointing to the OAuth2 authorization endpoint.
     */
    protected function getAuthorizeUrl(): string
    {
        return 'https://signin.infusionsoft.com/app/oauth/authorize';
    }

    /**
     * URL pointing to the OAuth2 access token endpoint.
     */
    protected function getAccessTokenUrl(): string
    {
        return 'https://api.infusionsoft.com/token';
    }

    protected function getApiRootUrl(): string
    {
        return 'https://api.infusionsoft.com/crm/rest/v1/';
    }

    /**
     * This method returns an array of the built-in fields and their mapping paths.
     *
     * @return array
     */
    private static function getDefaultFields()
    {
        $fieldList = [];

        // Default fields in Infusionsoft - we're namespacing them to make sure we can process the request properly
        // Done this way to get around the way Infusionsoft's API handles built-in and 2-dimensional fields.
        // The key is composed as such: <fieldType>:<fieldHandle>:<nestedPropertyOnFieldHandle>
        // The field type gets stripped off; however, we need it to determine how to handle the field. See processFields
        $fieldList[] = new FieldObject('default:given_name', 'First Name', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:middle_name', 'Middle Name', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:family_name', 'Last Name', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:suffix', 'Name Suffix', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:preferred_name', 'Preferred Name', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:website', 'Website', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:time_zone', 'Timezone', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:spouse_name', 'Spouse Name', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:opt_in_reason', 'Opt in Reason', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:lead_source_id', 'Lead Source ID', FieldObject::TYPE_NUMERIC);
        $fieldList[] = new FieldObject('default:job_title', 'Job Title', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('default:owner_id', 'Owner ID', FieldObject::TYPE_NUMERIC);

        // Default email address field
        $fieldList[] = new FieldObject('email_addresses:EMAIL1:email', 'Email Address 1', FieldObject::TYPE_STRING);

        // Address fields (Infusionsoft calls it BILLING - but addresses is an array of objects)
        // See sample https://developer.infusionsoft.com/docs/rest/#!/Contact/createOrUpdateContactUsingPUT
        $fieldList[] = new FieldObject('addresses:BILLING:country_code', 'Billing Address: Country Code', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:line1', 'Billing Address: Line 1', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:line2', 'Billing Address: Line 2', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:locality', 'Billing Address: Locality', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:postal_code', 'Billing Address: Postal Code', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:region', 'Billing Address: Region', FieldObject::TYPE_STRING);
        $fieldList[] = new FieldObject('addresses:BILLING:zip_code', 'Billing Address: Zip Code', FieldObject::TYPE_STRING);

        $fieldList[] = new FieldObject('phone_numbers:PHONE1:number', 'Phone Number', FieldObject::TYPE_STRING);

        $fieldList[] = new FieldObject('infusionsoftTagId', 'Apply Tag ID', FieldObject::TYPE_STRING);

        return $fieldList;
    }

    /**
     * @param mixed $data
     *
     * @return array
     */
    private function processFields($data)
    {
        // Infusionsoft wants custom fields in their own array
        $resultData = [
            'custom_fields' => [],
        ];

        // Now we need to construct our array based on the Freeform field mapping handles
        $complexData = [];

        foreach ($data as $fieldName => $fieldValue) {
            // Apply a tag if desired
            if ('infusionsoftTagId' === $fieldName) {
                $resultData['infusionsoftTagId'] = $fieldValue;

                continue;
            }

            // Deal with simple default - just rip off the fieldType (default:)
            if (0 === strpos($fieldName, 'default:')) {
                $processedFieldName = preg_replace('/^'.preg_quote('default:', '/').'/', '', $fieldName);

                $resultData[$processedFieldName] = $fieldValue;

                continue;
            }

            // Custom fields, we rip off the fieldHandle and insert as a 2d array into $resultData['customFields']
            if (0 === strpos($fieldName, 'custom:')) {
                $processedFieldName = preg_replace('/^'.preg_quote('custom:', '/').'/', '', $fieldName);

                $resultData['custom_fields'][] = [
                    'id' => $processedFieldName,
                    'content' => $fieldValue,
                ];

                continue;
            }

            // If it's not custom or default, it means it's a 2d field (handle and sub-properties)
            $fieldScopes = explode(':', $fieldName, 3);
            // Double-check we have 3 items between the colons
            if (3 === \count($fieldScopes)) {
                // I.E. email_addresses
                $fieldGroup = $fieldScopes[0];
                // I.E. BILLING
                $fieldHandle = $fieldScopes[1];
                // I.E. country_code
                $fieldParameter = $fieldScopes[2];

                // If we haven't started processing this field handle yet, create an array for it so we don't lose
                // existing properties

                // Check the type
                if (!isset($complexData[$fieldGroup])) {
                    $complexData[$fieldGroup] = [];
                }

                // Check the field handle and set the key
                if (!isset($complexData[$fieldGroup][$fieldHandle])) {
                    $complexData[$fieldGroup][$fieldHandle] = [];
                }

                // Construct our field property object
                $complexData[$fieldGroup][$fieldHandle][$fieldParameter] = $fieldValue;
            }
        }
        // This code will flatten the field name key to a value
        $flattenedData = [];
        foreach ($complexData as $fieldGroupHandle => $complexDatum) {
            $flattenedData[$fieldGroupHandle] = [];
            foreach ($complexDatum as $field => $item) {
                $item['field'] = $field;
                $flattenedData[$fieldGroupHandle][] = $item;
            }
        }

        return array_merge($resultData, $flattenedData);
    }

    /**
     * @throws CRMIntegrationNotFoundException
     * @throws IntegrationException
     * @throws \ReflectionException
     */
    private function generateAuthorizedClient(bool $refreshTokenIfExpired = true): Client
    {
        $client = new Client([
            'headers' => [
                'Authorization' => 'Bearer '.$this->getAccessToken(),
                'Content-Type' => 'application/json',
            ],
        ]);

        if ($refreshTokenIfExpired && !isset(self::$_REFRESHED_TOKENS[$this->getId()])) {
            try {
                $endpoint = $this->getEndpoint('/account/profile');
                $client->get($endpoint);
            } catch (RequestException $e) {
                if (401 === $e->getCode()) {
                    $accessToken = $this->refreshToken();
                    $client = new Client([
                        'headers' => [
                            'Authorization' => 'Bearer '.$accessToken,
                            'Content-Type' => 'application/json',
                        ],
                    ]);
                }
            }
        }

        return $client;
    }
}
