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

namespace Solspace\Freeform\Integrations\MailingLists;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\MailingLists\MailingListOAuthConnector;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;
use Solspace\Freeform\Records\IntegrationRecord;

class ConstantContact3 extends MailingListOAuthConnector
{
    public const TITLE = 'Constant Contact';
    public const LOG_CATEGORY = 'Constant Contact';
    public const SETTING_REFRESH_TOKEN = 'refresh_token';

    /**
     * Returns the MailingList service provider short name
     * i.e. - Mailchimp, Constant Contact, etc...
     */
    public function getServiceProvider(): string
    {
        return 'Constant Contact';
    }

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
                SettingBlueprint::TYPE_AUTO,
                self::SETTING_RETURN_URI,
                'Redirect URI',
                'You must specify this as the Return URI in your app settings to be able to authorize your credentials. DO NOT CHANGE THIS.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_CLIENT_ID,
                'API Key',
                'Enter the API Key of your app in here',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_CLIENT_SECRET,
                'App Secret',
                'Enter the Client Secret of your app here',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_REFRESH_TOKEN,
                'Refresh Token',
                'You should not set this',
                false
            ),
        ];
    }

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    public function checkConnection(bool $refreshTokenIfExpired = true): bool
    {
        // Having no Access Token is very likely because this is
        // an attempted connection right after a first save. The response
        // will definitely be an error so skip the connection in this
        // first-time connect situation.
        if ($this->getAccessToken()) {
            $client = $this->generateAuthorizedClient($refreshTokenIfExpired);
            $endpoint = $this->getEndpoint('/contact_lists');

            try {
                $response = $client->get($endpoint);
                $json = json_decode((string) $response->getBody(), false);

                return isset($json->lists);
            } catch (RequestException $exception) {
                throw new IntegrationException(
                    $exception->getMessage(),
                    $exception->getCode(),
                    $exception->getPrevious()
                );
            }
        }

        return false;
    }

    /**
     * @throws IntegrationException
     */
    public function pushEmails(ListObject $mailingList, array $emails, array $mappedValues): bool
    {
        $client = $this->generateAuthorizedClient();

        $values = [];
        foreach ($mappedValues as $key => $value) {
            if (preg_match('/^street_address_(.*)/', $key, $matches)) {
                if (!isset($values['street_address'])) {
                    $values['street_address'] = [];
                }

                $values['street_address'][$matches[1]] = $value;
            } elseif (preg_match('/^custom_(.*)/', $key, $matches)) {
                if (!isset($values['custom_fields'])) {
                    $values['custom_fields'] = [];
                }

                $values['custom_fields'][] = ['custom_field_id' => $matches[1], 'value' => $value];
            } else {
                $values[$key] = $value;
            }
        }

        if (isset($values['street_address']) && !isset($values['street_address']['kind'])) {
            $values['street_address']['kind'] = 'home';
        }

        try {
            $data = array_merge(
                [
                    'email_address' => $emails[0],
                    'create_source' => 'Contact',
                    'list_memberships' => [$mailingList->getId()],
                ],
                $values
            );

            $response = $client->post($this->getEndpoint('/contacts/sign_up_form'), ['json' => $data]);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not connect to API endpoint')
            );
        }

        $status = $response->getStatusCode();
        if (!\in_array($status, [200, 201])) { // 200 Contact successfully update, 201 Contact successfully created
            $this->getLogger()->error('Could not add contacts to list', ['response' => (string) $response->getBody()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not add emails to lists')
            );
        }

        return 201 === $status;
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
        $apiKey = $this->getClientId();
        $secret = $this->getClientSecret();

        if (!$apiKey || !$secret) {
            return false;
        }

        $payload = [
            'response_type' => 'code',
            'client_id' => $apiKey,
            'redirect_uri' => $this->getReturnUri(),
            'scope' => 'contact_data offline_access',
            'state' => session_id(),
        ];

        header('Location: '.$this->getAuthorizeUrl().'?'.http_build_query($payload));

        exit;
    }

    /**
     * @return null|string
     */
    public function getRefreshToken()
    {
        return $this->getSetting(self::SETTING_REFRESH_TOKEN);
    }

    /**
     * @param string $refreshToken
     *
     * @throws IntegrationException
     */
    public function setRefreshToken(string $refreshToken = null): self
    {
        $this->setSetting(self::SETTING_REFRESH_TOKEN, $refreshToken);

        return $this;
    }

    /**
     * @throws IntegrationException
     */
    public function updateAccessToken()
    {
        $record = $this->getIntegrationRecord();
        $record->accessToken = $this->getAccessToken();
        $record->save(false);
    }

    /**
     * @throws IntegrationException
     */
    public function updateSettings()
    {
        $record = $this->getIntegrationRecord();
        $record->settings = $this->getSettings();
        $record->save(false);
    }

    /**
     * Makes an API call that fetches mailing lists
     * Builds ListObject objects based on the results
     * And returns them.
     *
     * @return ListObject[]
     *
     * @throws IntegrationException
     */
    protected function fetchLists(): array
    {
        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/contact_lists');

        try {
            $response = $client->get($endpoint);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not connect to API endpoint')
            );
        }

        $status = $response->getStatusCode();
        if (200 !== $status) {
            $this->getLogger()->error(
                'Could not fetch Constant Contact lists',
                ['response' => (string) $response->getBody()]
            );

            throw new IntegrationException(
                $this->getTranslator()->translate(
                    'Could not fetch {serviceProvider} lists',
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        $json = json_decode((string) $response->getBody(), false);

        $lists = [];
        foreach ($json->lists as $list) {
            if (isset($list->list_id, $list->name)) {
                $lists[] = new ListObject(
                    $this,
                    $list->list_id,
                    $list->name,
                    $this->fetchFields($list->list_id)
                );
            }
        }

        return $lists;
    }

    /**
     * Fetch all custom fields for each list.
     *
     * @param string $listId
     *
     * @return FieldObject[]
     */
    protected function fetchFields($listId): array
    {
        static $cachedFields;

        if (null === $cachedFields) {
            $client = $this->generateAuthorizedClient();
            $endpoint = $this->getEndpoint('/contact_custom_fields');

            try {
                $response = $client->get($endpoint);
            } catch (RequestException $e) {
                $responseBody = (string) $e->getResponse()->getBody();
                $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

                throw new IntegrationException(
                    $this->getTranslator()->translate('Could not connect to API endpoint')
                );
            }

            $fields = [
                new FieldObject('first_name', 'First Name', FieldObject::TYPE_STRING, false),
                new FieldObject('last_name', 'Last Name', FieldObject::TYPE_STRING, false),
                new FieldObject('job_title', 'Job Title', FieldObject::TYPE_STRING, false),
                new FieldObject('company_name', 'Company Name', FieldObject::TYPE_STRING, false),
                new FieldObject('phone_number', 'Phone Number', FieldObject::TYPE_STRING, false),
                new FieldObject('anniversary', 'Anniversary', FieldObject::TYPE_STRING, false),
                new FieldObject('birthday_month', 'Birthday Month', FieldObject::TYPE_NUMERIC, false),
                new FieldObject('birthday_day', 'Birthday Day', FieldObject::TYPE_NUMERIC, false),
                new FieldObject('street_address_kind', 'Address: Kind', FieldObject::TYPE_STRING, false),
                new FieldObject('street_address_street', 'Address: Street', FieldObject::TYPE_STRING, false),
                new FieldObject('street_address_city', 'Address: City', FieldObject::TYPE_STRING, false),
                new FieldObject('street_address_state', 'Address: State', FieldObject::TYPE_STRING, false),
                new FieldObject('street_address_postal_code', 'Address: Postal Code', FieldObject::TYPE_STRING, false),
                new FieldObject('street_address_country', 'Address: Country', FieldObject::TYPE_STRING, false),
            ];

            $json = json_decode((string) $response->getBody(), false);
            foreach ($json->custom_fields as $field) {
                $fields[] = new FieldObject(
                    'custom_'.$field->custom_field_id,
                    $field->label,
                    FieldObject::TYPE_STRING,
                    false
                );
            }

            $cachedFields = $fields;
        }

        return $cachedFields;
    }

    /**
     * Returns the API root url without endpoints specified.
     */
    protected function getApiRootUrl(): string
    {
        return 'https://api.cc.email/v3';
    }

    /**
     * URL pointing to the OAuth2 authorization endpoint.
     */
    protected function getAuthorizeUrl(): string
    {
        return 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
    }

    /**
     * URL pointing to the OAuth2 access token endpoint.
     */
    protected function getAccessTokenUrl(): string
    {
        return 'https://authz.constantcontact.com/oauth2/default/v1/token';
    }

    /**
     * @throws IntegrationException
     */
    protected function onAfterFetchAccessToken(\stdClass $responseData)
    {
        if (isset($responseData->refresh_token)) {
            $this->setRefreshToken($responseData->refresh_token);
        }
    }

    /**
     * @throws IntegrationException
     */
    private function generateAuthorizedClient(bool $refreshTokenIfExpired = true): Client
    {
        $client = new Client(
            [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->getAccessToken(),
                    'Content-Type' => 'application/json',
                ],
            ]
        );

        if ($refreshTokenIfExpired) {
            try {
                $this->checkConnection(false);
            } catch (IntegrationException $e) {
                if (401 === $e->getCode()) {
                    $client = new Client(
                        [
                            'headers' => [
                                'Authorization' => 'Bearer '.$this->getRefreshedAccessToken(),
                                'Content-Type' => 'application/json',
                            ],
                        ]
                    );
                }
            }
        }

        return $client;
    }

    /**
     * @throws IntegrationException
     */
    private function getRefreshedAccessToken(): string
    {
        if (!$this->getRefreshToken() || !$this->getClientId() || !$this->getClientSecret()) {
            $this->getLogger()->warning(
                'Trying to refresh Constant Contact access token with no credentials present'
            );

            return 'invalid';
        }

        $client = new Client();
        $payload = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getRefreshToken(),
        ];

        try {
            $response = $client->post(
                $this->getAccessTokenUrl(),
                [
                    'auth' => [$this->getClientId(), $this->getClientSecret()],
                    'form_params' => $payload,
                ]
            );

            $json = json_decode((string) $response->getBody());
            if (!isset($json->access_token)) {
                throw new IntegrationException(
                    $this->getTranslator()->translate("No 'access_token' present in auth response for Constant Contact")
                );
            }

            $this->setAccessToken($json->access_token);
            $this->setRefreshToken($json->refresh_token);

            // The Record isn't being updated, as it would be with a regular
            // form save, so we need to update the Record ourselves.
            $this->updateAccessToken();
            $this->updateSettings();

            return $this->getAccessToken();
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $e->getMessage(),
                $e->getCode(),
                $e->getPrevious()
            );
        }
    }

    /**
     * @throws IntegrationException
     */
    private function getIntegrationRecord(): IntegrationRecord
    {
        $record = IntegrationRecord::findOne(['id' => $this->getId()]);

        if (!$record) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    'Mailing List integration with ID {id} not found',
                    ['id' => $this->getId()]
                )
            );
        }

        return $record;
    }
}
