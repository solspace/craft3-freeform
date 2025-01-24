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

use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Integrations\CRM\Zoho\AbstractZohoIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;

class ZohoDeal extends AbstractZohoIntegration
{
    public const TITLE = 'Zoho Deal';
    public const LOG_CATEGORY = 'Zoho';

    public const MODULE_DEALS = 'Deals';
    public const MODULE_ACCOUNTS = 'Accounts';
    public const MODULE_CONTACTS = 'Contacts';

    public const CATEGORY_DEAL = 'deal';
    public const CATEGORY_ACCOUNT = 'account';
    public const CATEGORY_CONTACT = 'contact';

    public const DEFAULT_CONTACT_ROLE = '4201883000000006871';

    public function getModule(): string
    {
        return 'Deals';
    }

    /**
     * Push objects to the CRM.
     *
     * @param array $formFields
     *
     * @throws \Exception
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $client = $this->generateAuthorizedClient();

        $dealMapping = $contactMapping = $accountMapping = [];
        foreach ($keyValueList as $key => $value) {
            if (empty($value) || !preg_match('/^(\w+)___(.*)$/', $key, $matches)) {
                continue;
            }

            [$_, $category, $handle] = $matches;

            switch ($category) {
                case self::CATEGORY_DEAL:
                    $dealMapping[$handle] = $value;

                    break;

                case self::CATEGORY_CONTACT:
                    $contactMapping[$handle] = $value;

                    break;

                case self::CATEGORY_ACCOUNT:
                    $accountMapping[$handle] = $value;

                    break;
            }
        }

        // Push Account
        $endpoint = $this->getEndpoint('/'.self::MODULE_ACCOUNTS.'/upsert');
        $accountId = null;

        try {
            $response = $client->post(
                $endpoint,
                ['json' => ['data' => [$accountMapping], 'duplicate_check_fields' => ['Account_Name']]]
            );

            $json = json_decode((string) $response->getBody(), true);

            $this->getHandler()->onAfterResponse($this, $response);

            if (isset($json['data'][0]['details']['id'])) {
                $accountId = $json['data'][0]['details']['id'];
            }
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

        // Push Contact
        $endpoint = $this->getEndpoint('/'.self::MODULE_CONTACTS.'/upsert');
        $contactId = null;

        try {
            $response = $client->post(
                $endpoint,
                ['json' => ['data' => [$contactMapping], 'duplicate_check_fields' => ['Email']]]
            );

            $json = json_decode((string) $response->getBody(), true);

            $this->getHandler()->onAfterResponse($this, $response);

            if (isset($json['data'][0]['details']['id'])) {
                $contactId = $json['data'][0]['details']['id'];
            }
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

        // Push Deal
        $endpoint = $this->getEndpoint('/'.self::MODULE_DEALS);
        $dealId = null;

        try {
            $response = $client->post($endpoint, ['json' => ['data' => [$dealMapping]]]);

            $this->getHandler()->onAfterResponse($this, $response);
            $json = json_decode((string) $response->getBody(), true);

            if (isset($json['data'][0]['details']['id'])) {
                $dealId = $json['data'][0]['details']['id'];
            }
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

        // Connect Contact to Deal
        $endpoint = $this->getEndpoint('/'.self::MODULE_CONTACTS.'/'.$contactId.'/'.self::MODULE_DEALS.'/'.$dealId);

        try {
            $client->put(
                $endpoint,
                ['json' => ['data' => [['Contact_Role' => self::DEFAULT_CONTACT_ROLE]]]]
            );
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage(), ['exception' => $e->getMessage()]);

            throw $e;
        }

        return true;
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        $client = $this->generateAuthorizedClient();

        $fieldEndpoints = [
            ['endpoint' => self::MODULE_DEALS, 'category' => 'deal'],
            ['endpoint' => self::MODULE_CONTACTS, 'category' => 'contact'],
            ['endpoint' => self::MODULE_ACCOUNTS, 'category' => 'account'],
        ];

        $fieldList = [];

        foreach ($fieldEndpoints as $item) {
            $category = $item['category'];
            $module = $item['endpoint'];

            try {
                $endpoint = $this->getEndpoint('/settings/fields?module='.$module);
                $response = $client->get($endpoint);
            } catch (RequestException $e) {
                $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);

                return [];
            }

            $data = json_decode((string) $response->getBody());

            foreach ($data->fields as $field) {
                if ($field->read_only || $field->field_read_only) {
                    continue;
                }

                $jsonType = null;

                if (isset($field->json_type)) {
                    $jsonType = $field->json_type;
                }

                $fieldType = $this->convertFieldType($field->data_type, $jsonType);

                $fieldObject = new FieldObject(
                    $category.'___'.$field->api_name,
                    $field->field_label." ({$module})",
                    $fieldType
                );

                $fieldList[] = $fieldObject;
            }
        }

        return $fieldList;
    }
}
