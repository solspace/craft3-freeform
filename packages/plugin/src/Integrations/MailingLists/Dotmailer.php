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
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationStorageInterface;
use Solspace\Freeform\Library\Integrations\MailingLists\AbstractMailingListIntegration;
use Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class Dotmailer extends AbstractMailingListIntegration
{
    public const SETTING_USER_EMAIL = 'user_email';
    public const SETTING_USER_PASS = 'user_pass';
    public const SETTING_DOUBLE_OPT_IN = 'double_opt_in';
    public const SETTING_ENDPOINT = 'endpoint';

    public const TITLE = 'Dotmailer';
    public const LOG_CATEGORY = 'Dotmailer';

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
                self::SETTING_USER_EMAIL,
                'API User Email',
                'Enter your Dotmailer API user email.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_PASSWORD,
                self::SETTING_USER_PASS,
                'Password',
                'Enter your Dotmailer API user password',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_BOOL,
                self::SETTING_DOUBLE_OPT_IN,
                'Use double opt-in?',
                '',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_ENDPOINT,
                'Endpoint',
                '',
                false
            ),
        ];
    }

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    public function checkConnection(): bool
    {
        $client = new Client();

        try {
            $response = $client->get(
                $this->getEndpoint('/account-info'),
                ['auth' => [$this->getUsername(), $this->getPassword()]]
            );

            $json = json_decode((string) $response->getBody());

            return isset($json->id) && !empty($json->id);
        } catch (RequestException $e) {
            $this->getLogger()->error((string) $e->getRequest()->getBody());

            throw new IntegrationException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Push emails to a specific mailing list for the service provider.
     *
     * @throws IntegrationException
     */
    public function pushEmails(ListObject $mailingList, array $emails, array $mappedValues): bool
    {
        $client = new Client();
        $endpoint = $this->getEndpoint('/address-books/'.$mailingList->getId().'/contacts');

        try {
            foreach ($emails as $email) {
                $data = [
                    'email' => $email,
                    'optInType' => $this->getSetting(self::SETTING_DOUBLE_OPT_IN) ? 'verifiedDouble' : 'single',
                ];

                if ($mappedValues) {
                    $data['dataFields'] = [];
                    foreach ($mappedValues as $key => $value) {
                        $data['dataFields'][] = [
                            'key' => $key,
                            'value' => $value,
                        ];
                    }
                }

                $response = $client->post(
                    $endpoint,
                    [
                        'auth' => [$this->getUsername(), $this->getPassword()],
                        'json' => $data,
                    ]
                );

                $this->getHandler()->onAfterResponse($this, $response);
            }
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not connect to API endpoint')
            );
        }

        return true;
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
     */
    public function fetchAccessToken(): string
    {
        return $this->getSetting(self::SETTING_USER_EMAIL);
    }

    /**
     * Perform anything necessary before this integration is saved.
     *
     * @throws IntegrationException
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
        $client = new Client();
        $endpoint = 'https://api.dotmailer.com/v2/account-info';

        try {
            $response = $client->get($endpoint, ['auth' => [$this->getUsername(), $this->getPassword()]]);
            $json = json_decode((string) $response->getBody());

            if (isset($json->properties)) {
                foreach ($json->properties as $property) {
                    if ('ApiEndpoint' === $property->name) {
                        $this->setSetting(self::SETTING_ENDPOINT, $property->value);
                        $model->updateSettings($this->getSettings());

                        return;
                    }
                }
            }
        } catch (BadResponseException $e) {
        }

        throw new IntegrationException('Could not get an API endpoint');
    }

    /**
     * Makes an API call that fetches mailing lists
     * Builds ListObject objects based on the results
     * And returns them.
     *
     * @return \Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject[]
     *
     * @throws IntegrationException
     */
    protected function fetchLists(): array
    {
        $client = new Client();
        $endpoint = $this->getEndpoint('/address-books');

        try {
            $response = $client->get(
                $endpoint,
                [
                    'auth' => [$this->getUsername(), $this->getPassword()],
                    'query' => ['select' => 1000],
                ]
            );
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not connect to API endpoint')
            );
        }

        if (200 !== $response->getStatusCode()) {
            throw new IntegrationException(
                $this->getTranslator()->translate(
                    'Could not fetch {serviceProvider} lists',
                    ['serviceProvider' => $this->getServiceProvider()]
                )
            );
        }

        $json = json_decode((string) $response->getBody());

        $lists = [];
        foreach ($json as $list) {
            if (isset($list->id, $list->name)) {
                $lists[] = new ListObject(
                    $this,
                    $list->id,
                    $list->name,
                    $this->fetchFields($list->id),
                    $list->contacts
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
     *
     * @throws IntegrationException
     */
    protected function fetchFields($listId): array
    {
        $client = new Client();
        $endpoint = $this->getEndpoint('/data-fields');

        try {
            $response = $client->get($endpoint, ['auth' => [$this->getUsername(), $this->getPassword()]]);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();
            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);

            throw new IntegrationException(
                $this->getTranslator()->translate('Could not connect to API endpoint')
            );
        }

        $json = json_decode((string) $response->getBody());

        if ($json) {
            $fieldList = [];
            foreach ($json as $field) {
                switch ($field->type) {
                    case 'String':
                    case 'Date':
                        $type = FieldObject::TYPE_STRING;

                        break;

                    case 'Boolean':
                        $type = FieldObject::TYPE_BOOLEAN;

                        break;

                    case 'Numeric':
                        $type = FieldObject::TYPE_NUMERIC;

                        break;

                    default:
                        $type = null;

                        break;
                }

                if (null === $type) {
                    continue;
                }

                $fieldList[] = new FieldObject(
                    $field->name,
                    $field->name,
                    $type,
                    false
                );
            }

            return $fieldList;
        }

        return [];
    }

    /**
     * Returns the API root url without endpoints specified.
     */
    protected function getApiRootUrl(): string
    {
        return rtrim($this->getSetting(self::SETTING_ENDPOINT), '/').'/v2/';
    }

    /**
     * @throws IntegrationException
     */
    private function getUsername(): string
    {
        return $this->getSetting(self::SETTING_USER_EMAIL) ?? '';
    }

    /**
     * @throws IntegrationException
     */
    private function getPassword(): string
    {
        return $this->getSetting(self::SETTING_USER_PASS) ?? '';
    }
}
