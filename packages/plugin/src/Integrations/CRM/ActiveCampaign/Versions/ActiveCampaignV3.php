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

namespace Solspace\Freeform\Integrations\CRM\ActiveCampaign\Versions;

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
use Solspace\Freeform\Integrations\CRM\ActiveCampaign\BaseActiveCampaignIntegration;

#[Type(
    name: 'ActiveCampaign',
    type: Type::TYPE_CRM,
    version: 'v3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class ActiveCampaignV3 extends BaseActiveCampaignIntegration
{
    protected const API_VERSION = '3';

    // ==========================================
    //                Contact
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Contacts')]
    #[Input\Boolean(
        label: 'Map to Contact',
        instructions: 'Should map to the Contact endpoint.',
        order: 8,
    )]
    protected bool $mapContact = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapContact)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable ActiveCampaign Contact fields',
        order: 9,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CONTACT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $contactMapping = null;

    // ==========================================
    //                  Deal
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Deals')]
    #[Input\Boolean(
        label: 'Map to Deal',
        instructions: 'Should map to the Deal endpoint.',
        order: 10,
    )]
    protected bool $mapDeal = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapDeal)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable ActiveCampaign Deal fields',
        order: 11,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_DEAL,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $dealMapping = null;

    // ==========================================
    //                Account
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Accounts')]
    #[Input\Boolean(
        label: 'Map to Account',
        instructions: 'Should map to the Account endpoint.',
        order: 12,
    )]
    protected bool $mapAccount = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapAccount)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable ActiveCampaign Account fields',
        order: 13,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_ACCOUNT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $accountMapping = null;

    private array $contactProps = [];

    private array $dealProps = [];

    private array $contact = [];

    private array $deal = [];

    private array $account = [];

    private int $contactId = 0;

    private int $accountId = 0;

    public function getApiRootUrl(): string
    {
        $url = $this->getApiUrl();

        $url = rtrim($url, '/');

        return $url.'/api/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        $this->setProps($form);
        $this->processAccount($client);
        $this->processContact($client);
        $this->processDeal($client);
    }

    private function setProps(Form $form): void
    {
        if ($this->mapContact) {
            $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
            if (!$mapping) {
                return;
            }

            foreach ($mapping as $key => $value) {
                if (is_numeric($key)) {
                    if (\is_array($value)) {
                        $value = '||'.implode('||', $value).'||';
                    }

                    $this->contactProps[] = [
                        'contact' => null,
                        'field' => (string) $key,
                        'value' => $value,
                    ];
                } else {
                    $this->contact[$key] = $value;
                }
            }
        }

        if ($this->mapDeal) {
            $mapping = $this->processMapping($form, $this->dealMapping, self::CATEGORY_DEAL);
            if (!$mapping) {
                return;
            }

            foreach ($mapping as $key => $value) {
                if (is_numeric($key)) {
                    if (\is_array($value)) {
                        $value = '||'.implode('||', $value).'||';
                    }

                    $this->dealProps[] = [
                        'dealId' => null,
                        'customFieldId' => (int) $key,
                        'fieldValue' => $value,
                    ];
                } else {
                    $this->deal[$key] = $value;
                }
            }
        }

        if ($this->mapAccount) {
            $mapping = $this->processMapping($form, $this->accountMapping, self::CATEGORY_ACCOUNT);
            if (!$mapping) {
                return;
            }

            foreach ($mapping as $key => $value) {
                $this->account[$key] = $value;
            }
        }
    }

    private function processAccount(Client $client): void
    {
        if (!$this->mapAccount) {
            return;
        }

        if (!$this->account) {
            $this->logger->debug('No Account mapping found');

            return;
        }

        try {
            $response = $client->post(
                $this->getEndpoint('/accounts'),
                ['json' => ['account' => $this->account]],
            );

            $this->triggerAfterResponseEvent(self::CATEGORY_ACCOUNT, $response);

            $json = json_decode((string) $response->getBody(), false);

            if (isset($json->account)) {
                $this->accountId = $json->account->id;
                $this->logger->info('New Account created', ['id' => $this->accountId]);
                $this->logger->debug('With Mapping', $this->account);
            }
        } catch (\Exception $exception) {
            if (422 === $exception->getCode()) {
                $response = $client->get($this->getEndpoint('/accounts'));

                $json = json_decode($response->getBody(), false);

                foreach ($json->accounts as $account) {
                    if (!empty($this->account['name']) && strtolower($account->name) === strtolower($this->account['name'])) {
                        $this->accountId = $account->id;
                        $this->logger->debug('Account already exists', ['id' => $account->id]);

                        break;
                    }
                }
            } else {
                $this->logger->debug('Account creation failed', ['exception' => $exception->getMessage()]);

                throw $exception;
            }
        }
    }

    private function processContact(Client $client): void
    {
        if (!$this->mapContact) {
            return;
        }

        if (!$this->contact) {
            $this->logger->debug('No Contact mapping found');

            return;
        }

        $listId = null;
        if (isset($this->contact['listId'])) {
            $listId = $this->contact['listId'];

            unset($this->contact['listId']);
        }

        $response = $client->post(
            $this->getEndpoint('/contact/sync'),
            ['json' => ['contact' => $this->contact]],
        );

        $json = json_decode($response->getBody(), false);

        if (isset($json->contact)) {
            $this->contactId = $json->contact->id;
            $this->logger->info('New Contact created', ['id' => $this->contactId]);
        }

        if ($this->accountId) {
            $this->contact['contact'] = $this->contactId;
            $this->contact['account'] = $this->accountId;

            $client->post(
                $this->getEndpoint('/accountContacts'),
                ['json' => ['accountContact' => $this->contact]],
            );

            $this->logger->info('Contact linked to Account', ['contact' => $this->contactId, 'account' => $this->accountId]);
        }

        foreach ($this->contactProps as $prop) {
            $prop['contact'] = (string) $this->contactId;

            $client->post(
                $this->getEndpoint('/fieldValues'),
                ['json' => ['fieldValue' => $prop]],
            );
        }

        if ((!$this->contactId && !$listId) || !$listId) {
            return;
        }

        $response = $client->post(
            $this->getEndpoint('/contactLists'),
            [
                'json' => [
                    'contactList' => [
                        'list' => $listId,
                        'contact' => $this->contactId,
                        'status' => 1,
                    ],
                ],
            ]
        );

        $this->logger->info('Contact added to List', ['contact' => $this->contactId, 'list' => $listId]);

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
    }

    private function processDeal(Client $client): void
    {
        if (!$this->mapDeal) {
            return;
        }

        if (!$this->deal) {
            $this->logger->debug('No Deal mapping found');

            return;
        }

        $pipelineId = $this->fetchPipelineId($client, $this->deal['group'] ?? $this->getPipeline());
        if ($pipelineId) {
            $this->deal['group'] = (string) $pipelineId;
        }

        $stageId = $this->fetchStageId($client, $this->deal['stage'] ?? $this->getStage(), $pipelineId);
        if ($stageId) {
            $this->deal['stage'] = (string) $stageId;
        }

        $ownerId = $this->fetchOwnerId($client, $this->deal['owner'] ?? $this->getOwner());
        if ($ownerId) {
            $this->deal['owner'] = (string) $ownerId;
        }

        if ($this->contactId) {
            $this->deal['contact'] = (string) $this->contactId;
        }

        $response = $client->post(
            $this->getEndpoint('/deals'),
            ['json' => ['deal' => $this->deal]],
        );

        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->deal)) {
            $this->logger->info('New Deal created', ['id' => $json->deal->id]);

            $dealId = $json->deal->id;

            foreach ($this->dealProps as $prop) {
                $prop['dealId'] = $dealId;

                $response = $client->post(
                    $this->getEndpoint('/dealCustomFieldData'),
                    ['json' => ['dealCustomFieldDatum' => $prop]],
                );

                $this->triggerAfterResponseEvent(self::CATEGORY_DEAL, $response);
            }
        }
    }
}
