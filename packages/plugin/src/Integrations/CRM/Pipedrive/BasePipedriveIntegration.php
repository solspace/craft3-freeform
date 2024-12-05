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

namespace Solspace\Freeform\Integrations\CRM\Pipedrive;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2RefreshTokenTrait;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BasePipedriveIntegration extends CRMIntegration implements OAuth2ConnectorInterface, OAuth2RefreshTokenInterface, PipedriveIntegrationInterface
{
    use OAuth2RefreshTokenTrait;
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'Pipedrive';

    protected const CATEGORY_LEAD = 'Lead';

    protected const CATEGORY_DEAL = 'Deal';

    protected const CATEGORY_ORGANIZATION = 'Organization';

    protected const CATEGORY_PERSON = 'Person';

    #[Flag(self::FLAG_INTERNAL)]
    #[Input\Hidden]
    protected string $apiDomain = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'User ID',
        instructions: 'Enter the Pipedrive User ID you want to assign to new objects.',
        order: 1,
    )]
    protected ?int $userId = null;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Boolean(
        instructions: 'Enable this setting to prevent creation of organizations or persons with overlapping names and/or email addresses.',
        order: 2,
    )]
    protected bool $detectDuplicates = false;

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/users/me'));
        $json = json_decode((string) $response->getBody(), false);

        return isset($json->success) && true === $json->success;
    }

    public function getAuthorizeUrl(): string
    {
        return 'https://oauth.pipedrive.com/oauth/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://oauth.pipedrive.com/oauth/token';
    }

    public function getApiDomain(): ?string
    {
        return $this->apiDomain;
    }

    public function setApiDomain(?string $apiDomain): self
    {
        $this->apiDomain = $apiDomain;

        return $this;
    }

    public function fetchFields(string $category, Client $client): array
    {
        $segment = match ($category) {
            self::CATEGORY_PERSON => '/personFields',
            self::CATEGORY_ORGANIZATION => '/organizationFields',
            self::CATEGORY_LEAD, self::CATEGORY_DEAL => '/dealFields',
        };

        $response = $client->get($this->getEndpoint($segment));
        $json = json_decode((string) $response->getBody());

        if (!isset($json->success) || !$json->success) {
            throw new IntegrationException('Could not fetch fields for '.$category);
        }

        $requiredFields = ['name', 'title'];

        $fieldList = [];

        foreach ($json->data as $field) {
            $type = match ($field->field_type) {
                'varchar', 'varchar_auto', 'text', 'date', 'enum', 'time', 'timerange', 'daterange' => FieldObject::TYPE_STRING,
                'int', 'double', 'monetary', 'user', 'org', 'people' => FieldObject::TYPE_NUMERIC,
                'set', 'phone' => FieldObject::TYPE_ARRAY,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $required = (bool) $field->mandatory_flag;
            if (\in_array($field->key, $requiredFields, true)) {
                $required = true;
            }

            $fieldList[] = new FieldObject(
                $field->key,
                $field->name,
                $type,
                $category,
                $required,
            );
        }

        if (self::CATEGORY_ORGANIZATION === $category) {
            $fieldList[] = new FieldObject('address', 'Address', FieldObject::TYPE_STRING, $category, false);
        }

        if (self::CATEGORY_DEAL === $category || self::CATEGORY_LEAD === $category) {
            $fieldList[] = new FieldObject('note', 'Note', FieldObject::TYPE_STRING, $category, false);
        }

        return $fieldList;
    }

    protected function getUserId(): ?int
    {
        return $this->getProcessedValue($this->userId);
    }

    protected function isDetectDuplicates(): bool
    {
        return $this->detectDuplicates;
    }
}
