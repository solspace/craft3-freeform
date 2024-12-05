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

namespace Solspace\Freeform\Integrations\CRM\ActiveCampaign;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseActiveCampaignIntegration extends CRMIntegration implements ActiveCampaignIntegrationInterface
{
    protected const LOG_CATEGORY = 'ActiveCampaign';

    protected const CATEGORY_CONTACT = 'Contact';

    protected const CATEGORY_DEAL = 'Deal';

    protected const CATEGORY_ACCOUNT = 'Account';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API Token',
        instructions: 'Enter your API Token here.',
        order: 1,
    )]
    protected string $apiToken = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API URL',
        instructions: 'Enter your API specific URL (e.g. "https://youraccountname.api-us1.com" or "https://youraccountname.activehosted.com").',
        order: 2,
    )]
    protected string $apiUrl = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Pipeline',
        instructions: 'Enter the name or ID of the desired Pipeline.',
        order: 3,
    )]
    protected string $pipeline = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Stage',
        instructions: 'Enter the name or ID of the desired Stage.',
        order: 4,
    )]
    protected string $stage = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Owner (Optional if auto-assign enabled in AC)',
        instructions: "Enter the username or ID of the user you wish to assign as the deal owner. If you don't specify an owner, Active Campaign will auto-assign an owner if it is set up.",
        order: 5,
    )]
    protected string $owner = '';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/webhooks'));

        return 200 === $response->getStatusCode();
    }

    public function getApiToken(): string
    {
        return $this->getProcessedValue($this->apiToken);
    }

    public function fetchFields(string $category, Client $client): array
    {
        return match ($category) {
            self::CATEGORY_CONTACT => $this->fetchContactFields($client, $category),
            self::CATEGORY_DEAL => $this->fetchDealFields($client, $category),
            self::CATEGORY_ACCOUNT => $this->fetchAccountFields($client, $category),
            default => [],
        };
    }

    protected function getApiUrl(): string
    {
        return $this->getProcessedValue($this->apiUrl);
    }

    protected function getPipeline(): string
    {
        return $this->getProcessedValue($this->pipeline);
    }

    protected function getStage(): string
    {
        return $this->getProcessedValue($this->stage);
    }

    protected function getOwner(): string
    {
        return $this->getProcessedValue($this->owner);
    }

    protected function fetchPipelineId(Client $client, mixed $pipeline): ?int
    {
        if (!$pipeline) {
            return null;
        }

        if (is_numeric($pipeline)) {
            return $pipeline;
        }

        $pipelineId = null;

        $response = $client->get(
            $this->getEndpoint('/dealGroups'),
            [
                'query' => [
                    'filters[title]' => $pipeline,
                ],
            ],
        );

        $json = json_decode($response->getBody(), false);

        if (isset($json->dealGroups) && \count($json->dealGroups)) {
            $item = $json->dealGroups[0];

            $pipelineId = $item->id;
        }

        return $pipelineId;
    }

    protected function fetchStageId(Client $client, mixed $stage, mixed $pipelineId): ?int
    {
        if (!$stage) {
            return null;
        }

        if (is_numeric($stage)) {
            return $stage;
        }

        if (!$pipelineId) {
            return null;
        }

        if (!is_numeric($pipelineId)) {
            return null;
        }

        $stageId = null;

        $response = $client->get(
            $this->getEndpoint('/dealStages'),
            [
                'query' => [
                    'filters[title]' => $stage,
                    'filters[d_groupid]' => $pipelineId,
                ],
            ],
        );

        $json = json_decode($response->getBody(), false);

        if (isset($json->dealStages) && \count($json->dealStages)) {
            $item = $json->dealStages[0];

            $stageId = $item->id;
        }

        return $stageId;
    }

    protected function fetchOwnerId(Client $client, mixed $owner): ?int
    {
        if (!$owner) {
            return null;
        }

        if (is_numeric($owner)) {
            return $owner;
        }

        $ownerId = null;
        $response = $client->get($this->getEndpoint('/users/username/'.$owner));
        $json = json_decode($response->getBody(), false);

        if (!empty($json->user)) {
            $ownerId = $json->user->id;
        }

        return $ownerId;
    }

    private function fetchContactFields(Client $client, string $category): array
    {
        $response = $client->get($this->getEndpoint('/fields?limit=999'));
        $json = json_decode((string) $response->getBody(), false);

        $fieldList = [];

        $fieldList[] = new FieldObject('listId', 'List', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('firstName', 'First Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('lastName', 'Last Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email', 'Email', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('phone', 'Phone', FieldObject::TYPE_STRING, $category, false);

        if (isset($json->fields)) {
            foreach ($json->fields as $field) {
                $type = match ($field->type) {
                    'dropdown', 'multiselect', 'checkbox', 'listbox' => FieldObject::TYPE_ARRAY,
                    'date' => FieldObject::TYPE_DATE,
                    default => FieldObject::TYPE_STRING,
                };

                $fieldList[] = new FieldObject(
                    $field->id,
                    $field->title,
                    $type,
                    $category,
                    (bool) $field->isrequired,
                );
            }
        }

        return $fieldList;
    }

    private function fetchDealFields(Client $client, string $category): array
    {
        $response = $client->get($this->getEndpoint('/dealCustomFieldMeta'));

        $json = json_decode((string) $response->getBody(), false);

        $fieldList = [];

        $fieldList[] = new FieldObject('title', 'Title', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('description', 'Description', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('value', 'Value', FieldObject::TYPE_NUMERIC, $category, true);
        $fieldList[] = new FieldObject('currency', 'Currency', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('group', 'Group', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('owner', 'Owner', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('percent', 'Percent', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('stage', 'Stage', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('status', 'Status', FieldObject::TYPE_NUMERIC, $category, false);

        if (isset($json->dealCustomFieldMeta)) {
            foreach ($json->dealCustomFieldMeta as $field) {
                $type = match ($field->fieldType) {
                    'dropdown', 'multiselect', 'checkbox' => FieldObject::TYPE_ARRAY,
                    'date' => FieldObject::TYPE_DATETIME,
                    default => FieldObject::TYPE_STRING,
                };

                $fieldList[] = new FieldObject(
                    $field->id,
                    $field->fieldLabel,
                    $type,
                    $category,
                    (bool) $field->isRequired,
                );
            }
        }

        return $fieldList;
    }

    private function fetchAccountFields(Client $client, string $category): array
    {
        $response = $client->get($this->getEndpoint('/accountCustomFieldMeta'));
        $json = json_decode((string) $response->getBody());

        $fieldList = [
            new FieldObject('name', 'Name', FieldObject::TYPE_STRING, $category, true),
        ];

        if (isset($json->accountCustomFieldMeta)) {
            foreach ($json->accountCustomFieldMeta as $field) {
                $type = match ($field->fieldType) {
                    'dropdown', 'multiselect', 'checkbox' => FieldObject::TYPE_ARRAY,
                    'date' => FieldObject::TYPE_DATETIME,
                    default => FieldObject::TYPE_STRING,
                };

                $fieldList[] = new FieldObject(
                    $field->id,
                    $field->fieldLabel,
                    $type,
                    $category,
                    (bool) $field->isRequired,
                );
            }
        }

        return $fieldList;
    }
}
