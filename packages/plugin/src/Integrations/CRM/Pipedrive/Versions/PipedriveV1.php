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

namespace Solspace\Freeform\Integrations\CRM\Pipedrive\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Delimiter;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\CRM\Pipedrive\BasePipedriveIntegration;

#[Type(
    name: 'Pipedrive',
    type: Type::TYPE_CRM,
    version: 'v1',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class PipedriveV1 extends BasePipedriveIntegration
{
    protected const API_VERSION = 'v1';

    // ==========================================
    //                   Leads
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Leads')]
    #[Input\Boolean(
        label: 'Map to Leads',
        instructions: 'Should map to the Leads endpoint.',
        order: 3,
    )]
    protected bool $mapLeads = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(values.mapLeads)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pipedrive Lead fields',
        order: 4,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_LEAD,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $leadMapping = null;

    // ==========================================
    //                   Deals
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Deals')]
    #[Input\Boolean(
        label: 'Map to Deals',
        instructions: 'Should map to the Deals endpoint.',
        order: 5,
    )]
    protected bool $mapDeals = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapDeals)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pipedrive Deal fields',
        order: 6,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_DEAL,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $dealMapping = null;

    // ==========================================
    //                 Stage ID
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapDeals)')]
    #[Input\Text(
        label: 'Stage ID',
        instructions: 'Enter the Pipedrive Stage ID you want the deal to be placed in.',
        order: 7,
    )]
    protected ?int $stageId = null;

    // ==========================================
    //               Organization
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Organizations')]
    #[Input\Boolean(
        label: 'Map to Organization',
        instructions: 'Should map to the Organization endpoint.',
        order: 8,
    )]
    protected bool $mapOrganization = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapOrganization)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pipedrive Organization fields',
        order: 9,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_ORGANIZATION,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $organizationMapping = null;

    // ==========================================
    //                 Person
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Persons')]
    #[Input\Boolean(
        label: 'Map to Person',
        instructions: 'Should map to the Person endpoint.',
        order: 10,
    )]
    protected bool $mapPerson = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapPerson)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Pipedrive Person fields',
        order: 11,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_PERSON,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $personMapping = null;

    private ?int $organizationId = null;

    private ?int $personId = null;

    private ?string $leadId = null;

    private ?int $dealId = null;

    public function getApiRootUrl(): string
    {
        $url = 'https://api.pipedrive.com';

        $apiDomain = $this->getApiDomain();
        if ($apiDomain) {
            $url = $apiDomain;
        }

        $url = rtrim($url, '/');

        return $url.'/api/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        $this->processOrganization($form, $client);
        $this->processPerson($form, $client);
        $this->processLeads($form, $client);
        $this->processDeals($form, $client);
    }

    protected function getStageId(): ?int
    {
        return $this->getProcessedValue($this->stageId);
    }

    private function processOrganization(Form $form, Client $client): void
    {
        if (!$this->mapOrganization) {
            $this->logger->debug('No Organization mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->organizationMapping, self::CATEGORY_ORGANIZATION);
        if (!$mapping) {
            return;
        }

        $userId = $this->getUserId();
        if ($userId) {
            $mapping['owner_id'] = $userId;
        }

        $organizationId = $this->searchForDuplicate(
            $client,
            ['name' => $mapping['name'] ?? null],
            'organization',
        );

        if ($organizationId) {
            $this->organizationId = $organizationId;
        }

        $response = $client->post(
            $this->getEndpoint('/organizations'),
            ['json' => $mapping],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_ORGANIZATION, $response);

        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->data->id)) {
            $this->organizationId = (int) $json->data->id;
            $this->logger->info('New Organization created', ['id' => $this->organizationId]);
            $this->logger->debug('With Mapping', $mapping);
        }
    }

    private function processPerson(Form $form, Client $client): void
    {
        if (!$this->mapPerson) {
            $this->logger->debug('No Person mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->personMapping, self::CATEGORY_PERSON);
        if (!$mapping) {
            return;
        }

        $personId = $this->searchForDuplicate(
            $client,
            ['email' => $mapping['email'] ?? null],
            'person',
        );

        $userId = $this->getUserId();
        if ($userId) {
            $mapping['owner_id'] = $userId;
        }

        if ($this->organizationId) {
            $mapping['org_id'] = $this->organizationId;
        }

        if ($personId) {
            unset($mapping['email']);

            $response = $client->put(
                $this->getEndpoint('/persons/'.$personId),
                ['json' => $mapping],
            );

            $this->logger->info('Existing Person updated', ['id' => $personId]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            [$response, $json] = $this->getJsonResponse($client->post(
                $this->getEndpoint('/persons'),
                ['json' => $mapping],
            ));

            $this->logger->info('New Person created', ['id' => $json?->data?->id]);
            $this->logger->debug('With Mapping', $mapping);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_PERSON, $response);

        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->data->id)) {
            $this->personId = (int) $json->data->id;
        }
    }

    private function processLeads(Form $form, Client $client): void
    {
        if (!$this->mapLeads) {
            $this->logger->debug('No Lead mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->leadMapping, self::CATEGORY_LEAD);
        if (!$mapping) {
            return;
        }

        $userId = $this->getUserId();
        if ($userId) {
            $mapping['owner_id'] = $userId;
        }

        if ($this->personId) {
            $mapping['person_id'] = $this->personId;
        }

        if ($this->organizationId) {
            $mapping['organization_id'] = $this->organizationId;
        }

        $value = new \stdClass();
        $value->amount = $mapping['value'] ?? 0;
        $value->currency = $mapping['currency'] ?? 'USD';

        unset($mapping['currency']);

        $mapping['value'] = $value->amount ? $value : null;

        $note = $mapping['note'] ?? false;
        unset($mapping['note']);

        $response = $client->post(
            $this->getEndpoint('/leads'),
            ['json' => $mapping],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_LEAD, $response);

        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->data->id)) {
            $this->leadId = $json->data->id;
            $this->logger->info('New Lead created', ['id' => $this->leadId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if (!empty($note)) {
            $json = [
                'content' => $note,
                'lead_id' => $this->leadId,
                'pinned_to_lead_flag' => '1',
            ];

            $this->addNote($client, $json);
        }
    }

    private function processDeals(Form $form, Client $client): void
    {
        if (!$this->mapDeals) {
            $this->logger->debug('No Deal mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->dealMapping, self::CATEGORY_DEAL);
        if (!$mapping) {
            return;
        }

        $userId = $this->getUserId();
        if ($userId) {
            $mapping['user_id'] = $userId;
        }

        $stageId = $this->getStageId();
        if ($stageId) {
            $mapping['stage_id'] = $stageId;
        }

        if ($this->personId) {
            $mapping['person_id'] = $this->personId;
        }

        if ($this->organizationId) {
            $mapping['org_id'] = $this->organizationId;
        }

        $note = $mapping['note'] ?? false;
        unset($mapping['note']);

        $response = $client->post(
            $this->getEndpoint('/deals'),
            ['json' => $mapping],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_DEAL, $response);

        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->data->id)) {
            $this->dealId = (int) $json->data->id;
            $this->logger->info('New Deal created', ['id' => $this->dealId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if (!empty($note)) {
            $json = [
                'content' => $note,
                'deal_id' => $this->dealId,
                'pinned_to_deal_flag' => '1',
            ];

            $this->addNote($client, $json);
        }
    }

    private function addNote(Client $client, array $json): void
    {
        $response = $client->post(
            $this->getEndpoint('/notes'),
            ['json' => $json],
        );

        $this->logger->info('Note added to entity', $json);

        $this->triggerAfterResponseEvent('note', $response);
    }

    private function searchForDuplicate(Client $client, array $terms, string $type): ?int
    {
        if (!$this->isDetectDuplicates()) {
            return null;
        }

        foreach ($terms as $field => $searchTerms) {
            if (!\is_array($searchTerms)) {
                $searchTerms = [$searchTerms];
            }

            foreach ($searchTerms as $term) {
                if (\strlen($term) < 2) {
                    continue;
                }

                $response = $client->get(
                    $this->getEndpoint('/itemSearch'),
                    [
                        'query' => [
                            'term' => $term,
                            'item_types' => $type,
                            'fields' => $field,
                            'exact_match' => true,
                            'limit' => 1,
                        ],
                    ]
                );

                $json = json_decode((string) $response->getBody(), false);
                if (\count($json->data->items) > 0) {
                    return (int) $json->data->items[0]->item->id;
                }
            }
        }

        return null;
    }
}
