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
use Psr\Http\Message\ResponseInterface;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationStorageInterface;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class SharpSpring extends AbstractCRMIntegration
{
    public const SETTING_SECRET_KEY = 'secret_key';
    public const SETTING_ACCOUNT_ID = 'account_id';
    public const TITLE = 'SharpSpring';
    public const LOG_CATEGORY = 'SharpSpring';

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
                self::SETTING_ACCOUNT_ID,
                'Account ID',
                'Enter your Account ID here.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_SECRET_KEY,
                'Secret Key',
                'Enter your Secret Key here.',
                true
            ),
        ];
    }

    /**
     * Push objects to the CRM.
     *
     * @param null|mixed $formFields
     *
     * @throws IntegrationException
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $contactProps = [];

        foreach ($keyValueList as $key => $value) {
            preg_match('/^(\w+)___(.+)$/', $key, $matches);

            [$all, $target, $propName] = $matches;

            switch ($target) {
                case 'contact':
                case 'custom':
                    $contactProps[$propName] = $value;

                    break;
            }
        }

        $contactId = null;
        if ($contactProps) {
            try {
                $payload = $this->generatePayload('createLeads', ['objects' => [$contactProps]]);
                $response = $this->getResponse($payload);

                $data = json_decode((string) $response->getBody(), true);

                $this->getHandler()->onAfterResponse($this, $response);

                return isset($data['result']['error']) && (0 === \count($data['result']['error']));
            } catch (RequestException $e) {
                if ($e->getResponse()) {
                    $json = json_decode((string) $e->getResponse()->getBody());
                    $this->getLogger()->error($json, ['exception' => $e->getMessage()]);
                }
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

        return false;
    }

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    public function checkConnection(): bool
    {
        $payload = $this->generatePayload('getFields', ['where' => [], 'limit' => 1]);

        try {
            $response = $this->getResponse($payload);
            $json = json_decode((string) $response->getBody(), true);

            return isset($json['result']['field']);
        } catch (\Exception $e) {
            throw new IntegrationException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     *
     * @throws IntegrationException
     */
    public function fetchFields(): array
    {
        $response = $this->getResponse($this->generatePayload('getFields', ['where' => ['isCustom' => '1']]));
        $json = (string) $response->getBody();
        $data = json_decode($json, true);
        $fields = [];
        if (isset($data['result']['field'])) {
            $fields = $data['result']['field'];
        }

        $suffix = ' (Contact)';

        $fieldList = [
            new FieldObject('contact___emailAddress', "Email{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___firstName', "First Name{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___lastName', "Last Name{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___website', "Website{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___phoneNumber', "Phone Number{$suffix}", FieldObject::TYPE_NUMERIC, false),
            new FieldObject(
                'contact___phoneNumberExtension',
                "Phone Number Extension{$suffix}",
                FieldObject::TYPE_NUMERIC,
                false
            ),
            new FieldObject('contact___faxNumber', "Fax Number{$suffix}", FieldObject::TYPE_NUMERIC, false),
            new FieldObject('contact___mobilePhoneNumber', "Mobile Phone Number{$suffix}", FieldObject::TYPE_NUMERIC, false),
            new FieldObject('contact___street', "Street Address{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___city', "City{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___state', "State{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___zipcode', "Zip{$suffix}", FieldObject::TYPE_NUMERIC, false),
            new FieldObject('contact___companyName', "Company Name{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___industry', "Industry{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___description', "Description{$suffix}", FieldObject::TYPE_STRING, false),
            new FieldObject('contact___title', "Title{$suffix}", FieldObject::TYPE_STRING, false),
        ];

        foreach ($fields as $field) {
            $field = (object) $field;
            if (!$field || !\is_object($field)) {
                continue;
            }

            $type = null;

            switch ($field->dataType) {
                case 'text':
                case 'string':
                case 'picklist':
                case 'phone':
                case 'url':
                case 'textarea':
                case 'country':
                case 'checkbox':
                case 'date':
                case 'bit':
                case 'hidden':
                case 'state':
                case 'radio':
                case 'datetime':
                    $type = FieldObject::TYPE_STRING;

                    break;

                case 'int':
                    $type = FieldObject::TYPE_NUMERIC;

                    break;

                case 'boolean':
                    $type = FieldObject::TYPE_BOOLEAN;

                    break;
            }

            if (null === $type) {
                continue;
            }

            $fieldObject = new FieldObject(
                'custom___'.$field->systemName,
                $field->label.' (Custom Fields)',
                $type,
                false
            );

            $fieldList[] = $fieldObject;
        }

        return $fieldList;
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
    }

    /**
     * Perform anything necessary before this integration is saved.
     *
     * @throws IntegrationException
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
        $accountId = $this->getAccountID();
        $secretKey = $this->getSecretKey();

        // If one of these isn't present, we just return void
        if (!$accountId || !$secretKey) {
            return;
        }

        $model->updateSettings($this->getSettings());
    }

    /**
     * Authorizes the application
     * Returns the access_token.
     */
    public function fetchAccessToken(): string
    {
        return '';
    }

    /**
     * Get the base SharpSpring API URL.
     */
    protected function getApiRootUrl(): string
    {
        return 'https://api.sharpspring.com/pubapi/v1.2/';
    }

    /**
     * Gets the API secret for SharpSpring from settings config.
     *
     * @return null|mixed
     *
     * @throws IntegrationException
     */
    private function getSecretKey()
    {
        return $this->getSetting(self::SETTING_SECRET_KEY);
    }

    /**
     * Gets the account ID for SharpSpring from settings config.
     *
     * @return null|mixed
     *
     * @throws IntegrationException
     */
    private function getAccountID()
    {
        return $this->getSetting(self::SETTING_ACCOUNT_ID);
    }

    /**
     * Generate a properly formatted payload for SharpSpring API.
     *
     * @param string $method
     * @param string $id
     */
    private function generatePayload($method, array $params = ['where' => []], $id = 'freeform'): array
    {
        return [
            'method' => $method,
            'params' => $params,
            'id' => $id,
        ];
    }

    private function getResponse(array $payload): ResponseInterface
    {
        $client = new Client();

        return $client->post(
            $this->getApiRootUrl(),
            [
                'query' => [
                    'accountID' => $this->getAccountID(),
                    'secretKey' => $this->getSecretKey(),
                ],
                'json' => $payload,
            ]
        );
    }
}
