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

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Integrations\CRM\Salesforce\AbstractSalesforceIntegration;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Exceptions\Integrations\CRMIntegrationNotFoundException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationStorageInterface;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class SalesforceLead extends AbstractSalesforceIntegration
{
    public const TITLE = 'Salesforce Lead';
    public const LOG_CATEGORY = 'Salesforce';

    public const SETTING_SITE_CLIENT_ID = 'client_id';
    public const SETTING_SITE_CLIENT_SECRET = 'client_secret';
    public const SETTING_SITE_USER_LOGIN = 'username';
    public const SETTING_SITE_USER_PASSWORD = 'password';
    public const SETTING_CLIENT_ID = 'salesforce_client_id';
    public const SETTING_CLIENT_SECRET = 'salesforce_client_secret';
    public const SETTING_USER_LOGIN = 'salesforce_username';
    public const SETTING_USER_PASSWORD = 'salesforce_password';
    public const SETTING_LEAD_OWNER = 'salesforce_lead_owner';
    public const SETTING_SANDBOX = 'salesforce_sandbox';
    public const SETTING_CUSTOM_URL = 'salesforce_custom_url';
    public const SETTING_TASKS_FOR_DUPLICATES = 'tasks_for_duplicates';
    public const SETTING_TASKS_SUBJECT = 'tasks_subject';
    public const SETTING_TASKS_DUE_DATE = 'tasks_due_date';
    public const SETTING_INSTANCE = 'instance';

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
                self::SETTING_SITE_CLIENT_ID,
                'Client ID',
                'Enter the Client ID of your Salesforce app in here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_PASSWORD,
                self::SETTING_SITE_CLIENT_SECRET,
                'Client Secret',
                'Enter the Client Secret of your Salesforce app here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_SITE_USER_LOGIN,
                'Username',
                'Enter your Salesforce username here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_PASSWORD,
                self::SETTING_SITE_USER_PASSWORD,
                'Password',
                'Enter your Salesforce password here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_LEAD_OWNER,
                'Assign Lead Owner?',
                'Enabling this will make Salesforce assign a lead owner based on lead owner assignment rules.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_SANDBOX,
                'Sandbox Mode',
                'Enable this if your Salesforce account is in Sandbox mode (connects to "test.salesforce.com" instead of "login.salesforce.com").',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_CUSTOM_URL,
                'Using custom URL?',
                'Enable this if you connect to your Salesforce account with a custom company URL (e.g. \'mycompany.my.salesforce.com\').',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_TASKS_FOR_DUPLICATES,
                'Convert Leads to Contact Tasks for Returning Customers?',
                'When a Salesforce Contact already exists with the same email address, create a new Task for the Contact instead of a new Lead.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_TASKS_SUBJECT,
                'Task Subject',
                'Enter the text you\'d like to have set for new Task subjects.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_TASKS_DUE_DATE,
                'Task Due Date',
                'Enter a relative textual date string for the Due Date of the newly created Task (e.g. \'2 days\').',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_CONFIG,
                self::SETTING_CLIENT_ID,
                'Client ID',
                'Enter the Client ID of your Salesforce app in here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_CONFIG,
                self::SETTING_CLIENT_SECRET,
                'Client Secret',
                'Enter the Client Secret of your Salesforce app here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_CONFIG,
                self::SETTING_USER_LOGIN,
                'Username',
                'Enter your Salesforce username here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_CONFIG,
                self::SETTING_USER_PASSWORD,
                'Password',
                'Enter your Salesforce password here.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_INSTANCE,
                'Instance',
                'This will be fetched automatically upon authorizing your credentials.',
                false
            ),
        ];
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
    }

    /**
     * Authorizes the application
     * Returns the access_token.
     *
     * @throws IntegrationException
     * @throws \Exception
     */
    public function fetchAccessToken(): string
    {
        $client = new Client();

        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        $username = $this->getUsername();
        $password = $this->getPassword();

        if (!$clientId || !$clientSecret || !$username || !$password) {
            throw new IntegrationException('Some or all of the configuration values are missing');
        }

        $payload = [
            'grant_type' => 'password',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'username' => $username,
            'password' => $password,
        ];

        try {
            $response = $client->post(
                $this->getAccessTokenUrl(),
                [
                    'form_params' => $payload,
                ]
            );

            $json = json_decode((string) $response->getBody());

            if (!isset($json->access_token)) {
                throw new IntegrationException(
                    $this->getTranslator()->translate(
                        "No 'access_token' present in auth response for {serviceProvider}",
                        ['serviceProvider' => $this->getServiceProvider()]
                    )
                );
            }

            $this->setAccessToken($json->access_token);
            $this->setAccessTokenUpdated(true);

            $this->onAfterFetchAccessToken($json);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw $e;
        }

        return $this->getAccessToken();
    }

    /**
     * Perform anything necessary before this integration is saved.
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
        $clientId = $this->getClientId();
        $clientSecret = $this->getClientSecret();
        $username = $this->getUsername();
        $password = $this->getPassword();

        // If one of these isn't present, we just return void
        if (!$clientId || !$clientSecret || !$username || !$password) {
            return;
        }

        $this->fetchAccessToken();
        $model->updateAccessToken($this->getAccessToken());
        $model->updateSettings($this->getSettings());
    }

    /**
     * Push objects to the CRM.
     *
     * @param null|AbstractField[] $formFields
     *
     * @throws IntegrationException
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $client = $this->generateAuthorizedClient();

        // Check for existing clients
        if ($this->isCreateTasksForDuplicates() && isset($keyValueList['Email'])) {
            $email = $keyValueList['Email'];

            $contact = $this->querySingle("SELECT Id, Email, OwnerId FROM Contact WHERE Email = '%s' LIMIT 1", [$email]);
            if ($contact) {
                $description = '';
                foreach ($formFields as $field) {
                    $description .= "{$field->getLabel()}: {$field->getValueAsString()}\n";
                }

                try {
                    $dueDate = $this->getSetting(self::SETTING_TASKS_DUE_DATE) ?: '+2 days';
                    $dueDate = new Carbon($dueDate, 'UTC');
                } catch (\Exception $e) {
                    $dueDate = new Carbon('+2 days', 'UTC');
                    $this->getLogger()->error($e->getMessage());
                }
                $subject = $this->getSetting(self::SETTING_TASKS_SUBJECT) ?: 'New Followup';

                $payload = [
                    'Subject' => $subject,
                    'WhoId' => $contact->Id,
                    'Description' => $description,
                    'ActivityDate' => $dueDate->toDateString(),
                ];

                if ($contact->OwnerId) {
                    $payload['OwnerId'] = $contact->OwnerId;
                }

                try {
                    $endpoint = $this->getEndpoint('/sobjects/Task');
                    $response = $client->post($endpoint, ['json' => $payload]);

                    if (201 === $response->getStatusCode()) {
                        return true;
                    }
                } catch (RequestException $exception) {
                }
            }
        }

        $endpoint = $this->getEndpoint('/sobjects/Lead');
        $setOwner = $this->getSetting(self::SETTING_LEAD_OWNER);
        $keyValueList = array_filter($keyValueList);

        try {
            $response = $client->post(
                $endpoint,
                [
                    'headers' => ['Sforce-Auto-Assign' => $setOwner ? 'TRUE' : 'FALSE'],
                    'json' => $keyValueList,
                ]
            );

            $this->getHandler()->onAfterResponse($this, $response);

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
                $errors = json_decode((string) $exceptionResponse->getBody());

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
        $endpoint = $this->getEndpoint('/');

        $response = $client->get($endpoint);

        $json = json_decode((string) $response->getBody(), true);

        return !empty($json);
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        $client = $this->generateAuthorizedClient();

        try {
            $response = $client->get($this->getEndpoint('/sobjects/Lead/describe'));
        } catch (RequestException $e) {
            $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);

            return [];
        }

        $data = json_decode((string) $response->getBody());

        $fieldList = [];
        foreach ($data->fields as $field) {
            if (!$field->updateable) {
                continue;
            }

            $type = null;

            switch ($field->type) {
                case 'string':
                case 'encryptedstring':
                case 'textarea':
                case 'email':
                case 'url':
                case 'address':
                case 'picklist':
                case 'phone':
                case 'reference':
                    $type = FieldObject::TYPE_STRING;

                    break;

                case 'boolean':
                    $type = FieldObject::TYPE_BOOLEAN;

                    break;

                case 'multipicklist':
                    $type = FieldObject::TYPE_ARRAY;

                    break;

                case 'int':
                case 'number':
                case 'currency':
                    $type = FieldObject::TYPE_NUMERIC;

                    break;

                case 'double':
                    $type = FieldObject::TYPE_FLOAT;

                    break;

                case 'date':
                    $type = FieldObject::TYPE_DATE;

                    break;

                case 'datetime':
                    $type = FieldObject::TYPE_DATETIME;

                    break;
            }

            if (null === $type) {
                continue;
            }

            $fieldObject = new FieldObject(
                $field->name,
                $field->label,
                $type,
                !$field->nillable
            );

            $fieldList[] = $fieldObject;
        }

        return $fieldList;
    }

    /**
     * Initiate a token refresh and fetch a refreshed token
     * Returns true on success.
     *
     * @throws IntegrationException
     */
    public function refreshToken(): bool
    {
        return (bool) $this->fetchAccessToken();
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

    /**
     * @throws CRMIntegrationNotFoundException
     */
    protected function onAfterFetchAccessToken(\stdClass $responseData)
    {
        if (!isset($responseData->instance_url)) {
            throw new CRMIntegrationNotFoundException("Salesforce response data doesn't contain the instance URL");
        }

        $this->setSetting(self::SETTING_INSTANCE, $responseData->instance_url);
    }

    /**
     * URL pointing to the OAuth2 authorization endpoint.
     */
    protected function getAuthorizeUrl(): string
    {
        return 'https://'.$this->getLoginUrl().'.salesforce.com/services/oauth2/authorize';
    }

    /**
     * URL pointing to the OAuth2 access token endpoint.
     */
    protected function getAccessTokenUrl(): string
    {
        return 'https://'.$this->getLoginUrl().'.salesforce.com/services/oauth2/token';
    }

    protected function getApiRootUrl(): string
    {
        $instance = $this->getSetting(self::SETTING_INSTANCE);
        $usingCustomUrls = $this->getSetting(self::SETTING_CUSTOM_URL);

        if (0 !== strpos($instance, 'https://')) {
            return sprintf(
                'https://%s%s.salesforce.com/services/data/v44.0/',
                $instance,
                $usingCustomUrls ? '.my' : ''
            );
        }

        return $instance.'/services/data/v44.0/';
    }

    protected function getAuthorizationCheckUrl(): string
    {
        return $this->getEndpoint('/sobjects/Lead/describe');
    }

    private function getLoginUrl(): string
    {
        $isSandboxMode = $this->getSetting(self::SETTING_SANDBOX);

        if ($isSandboxMode) {
            return 'test';
        }

        return 'login';
    }

    /**
     * @return null|mixed
     */
    private function getClientId()
    {
        return $this->getSetting(self::SETTING_SITE_CLIENT_ID) ?: $this->getSetting(self::SETTING_CLIENT_ID);
    }

    /**
     * @return null|mixed
     */
    private function getClientSecret()
    {
        return $this->getSetting(self::SETTING_SITE_CLIENT_SECRET) ?: $this->getSetting(self::SETTING_CLIENT_SECRET);
    }

    /**
     * @return null|mixed
     */
    private function getUsername()
    {
        return $this->getSetting(self::SETTING_SITE_USER_LOGIN) ?: $this->getSetting(self::SETTING_USER_LOGIN);
    }

    /**
     * @return null|mixed
     */
    private function getPassword()
    {
        return $this->getSetting(self::SETTING_SITE_USER_PASSWORD) ?: $this->getSetting(self::SETTING_USER_PASSWORD);
    }

    private function isCreateTasksForDuplicates(): bool
    {
        return $this->getSetting(self::SETTING_TASKS_FOR_DUPLICATES) ?: false;
    }
}
