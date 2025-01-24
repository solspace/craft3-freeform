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

class ZohoLead extends AbstractZohoIntegration
{
    public const TITLE = 'Zoho Lead';
    public const LOG_CATEGORY = 'Zoho';

    public function getModule(): string
    {
        return 'Leads';
    }

    /**
     * Push objects to the CRM.
     *
     * @param array $formFields
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $client = $this->generateAuthorizedClient();
        $endpoint = $this->getEndpoint('/'.$this->getModule());

        $keyValueList = array_filter($keyValueList);

        try {
            $response = $client->post($endpoint, ['json' => ['data' => [$keyValueList]]]);
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
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        $client = $this->generateAuthorizedClient();

        try {
            $endpoint = $this->getEndpoint('/settings/fields?module='.$this->getModule());
            $response = $client->get($endpoint);
        } catch (RequestException $e) {
            $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);

            return [];
        }

        $data = json_decode((string) $response->getBody());

        $fieldList = [];
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
                $field->api_name,
                $field->field_label,
                $fieldType
            );

            $fieldList[] = $fieldObject;
        }

        return $fieldList;
    }
}
