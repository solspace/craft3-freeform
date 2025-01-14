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

namespace Solspace\Freeform\Integrations\CRM\Zoho\Versions;

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
use Solspace\Freeform\Integrations\CRM\Zoho\BaseZohoIntegration;

#[Type(
    name: 'Zoho',
    type: Type::TYPE_CRM,
    version: 'v2',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class ZohoV2 extends BaseZohoIntegration
{
    protected const API_VERSION = 'v2';

    // ==========================================
    //                   Leads
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Leads')]
    #[Input\Boolean(
        label: 'Map Leads',
        instructions: 'Should map to the Leads endpoint.',
        order: 3,
    )]
    protected bool $mapLeads = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(values.mapLeads)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Zoho Lead fields',
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
        label: 'Map Deals',
        instructions: 'Should map to the Deals endpoint.',
        order: 5,
    )]
    protected bool $mapDeals = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapDeals)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Zoho Deals fields',
        order: 6,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_DEAL,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $dealMapping = null;

    // ==========================================
    //                  Stage
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapDeals)')]
    #[Input\Text(
        label: 'Deal Stage',
        instructions: 'Represents the stage of the deal. It can be: Qualification, Needs Analysis, Value Proposition, Identify Decision Makers, etc.',
        order: 7,
    )]
    protected ?string $stage = null;

    // ==========================================
    //                   Account
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Accounts')]
    #[Input\Boolean(
        label: 'Map Account',
        instructions: 'Should map to the Account endpoint.',
        order: 8,
    )]
    protected bool $mapAccount = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapAccount)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Zoho Account fields',
        order: 9,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_ACCOUNT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $accountMapping = null;

    // ==========================================
    //                   Contact
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Contacts')]
    #[Input\Boolean(
        label: 'Map Contact',
        instructions: 'Should map to the Contact endpoint.',
        order: 10,
    )]
    protected bool $mapContact = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapContact)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Zoho Contact fields',
        order: 11,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CONTACT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $contactMapping = null;

    // ==========================================
    //          Default Contact Role ID
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapContact)')]
    #[Input\Text(
        label: 'Default Contact Role ID',
        order: 12,
    )]
    protected ?string $defaultContactRoleId = null;

    private ?int $accountId = null;

    private ?int $contactId = null;

    private ?int $dealId = null;

    public function getAuthorizeUrl(): string
    {
        return $this->getDomain().'/oauth/'.self::API_VERSION.'/auth';
    }

    public function getAccessTokenUrl(): string
    {
        return $this->getDomain().'/oauth/'.self::API_VERSION.'/token';
    }

    public function getApiRootUrl(): string
    {
        $url = 'https://www.zohoapis.com';

        $apiDomain = $this->getApiDomain();
        if ($apiDomain) {
            $url = $apiDomain;
        }

        if ($this->isSandboxMode()) {
            $url = 'https://sandbox.zohoapis.'.$this->getLocation();
        }

        if ($this->isDeveloperMode()) {
            $url = 'https://developer.zohoapis.'.$this->getLocation();
        }

        $url = rtrim($url, '/');

        return $url.'/crm/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        $this->processLeads($form, $client);
        $this->processAccount($form, $client);
        $this->processContact($form, $client);
        $this->processDeals($form, $client);
    }

    protected function getStage(): ?string
    {
        return $this->stage;
    }

    protected function getDefaultContactRoleId(): ?string
    {
        return $this->defaultContactRoleId;
    }

    private function processLeads(Form $form, Client $client): void
    {
        if (!$this->mapLeads) {
            $this->logger->debug('No Leads mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->leadMapping, self::CATEGORY_LEAD);
        if (!$mapping) {
            return;
        }

        $response = $client->post(
            $this->getEndpoint('/Leads'),
            [
                'json' => [
                    'data' => [$mapping],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_LEAD, $response);

        $json = json_decode((string) $response->getBody(), true);

        $this->logger->info('New Lead created', ['id' => $json?->data?->id]);
        $this->logger->debug('With Mapping', $mapping);

        $this->processZohoResponseError($json);
    }

    private function processAccount(Form $form, Client $client): void
    {
        if (!$this->mapAccount) {
            $this->logger->debug('No Account mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->accountMapping, self::CATEGORY_ACCOUNT);
        if (!$mapping) {
            return;
        }

        $response = $client->post(
            $this->getEndpoint('/Accounts/upsert'),
            [
                'json' => [
                    'data' => [$mapping],
                    'duplicate_check_fields' => ['Account_Name'],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_ACCOUNT, $response);

        $json = json_decode((string) $response->getBody(), true);

        $this->processZohoResponseError($json);

        if (isset($json['data'][0])) {
            $data = $json['data'][0];

            $this->accountId = $data['details']['id'];
            $this->logger->info('New Account created', ['id' => $this->accountId]);
            $this->logger->debug('With Mapping', $mapping);
        }
    }

    private function processContact(Form $form, Client $client): void
    {
        if (!$this->mapContact) {
            $this->logger->debug('No Contact mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
        if (!$mapping) {
            return;
        }

        $mapping['Account_Name'] = ['id' => $this->accountId];

        $response = $client->post(
            $this->getEndpoint('/Contacts/upsert'),
            [
                'json' => [
                    'data' => [$mapping],
                    'duplicate_check_fields' => ['Email'],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);

        $json = json_decode((string) $response->getBody(), true);

        $this->processZohoResponseError($json);

        if (isset($json['data'][0])) {
            $data = $json['data'][0];

            $this->contactId = $data['details']['id'];
            $this->logger->info('New Contact created', ['id' => $this->contactId]);
            $this->logger->debug('With Mapping', $mapping);
        }
    }

    private function processDeals(Form $form, Client $client): void
    {
        if (!$this->mapDeals) {
            $this->logger->debug('No Deals mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->dealMapping, self::CATEGORY_DEAL);
        if (!$mapping) {
            return;
        }

        $mapping['Stage'] = $this->getStage() ?? 'Qualification';
        $mapping['Account_Name'] = ['id' => $this->accountId];
        $mapping['Contact_Name'] = ['id' => $this->contactId];

        $response = $client->post(
            $this->getEndpoint('/Deals'),
            [
                'json' => [
                    'data' => [$mapping],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_DEAL, $response);

        $json = json_decode((string) $response->getBody(), true);
        $this->processZohoResponseError($json);

        if (isset($json['data'][0])) {
            $data = $json['data'][0];

            $this->dealId = $data['details']['id'];
            $this->logger->info('New Deal created', ['id' => $this->dealId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if ($this->dealId && $this->contactId) {
            $response = $client->put(
                $this->getEndpoint('/Contacts/'.$this->contactId.'/Deals/'.$this->dealId),
                [
                    'json' => [
                        'data' => [
                            ['Contact_Role' => $this->getDefaultContactRoleId()],
                        ],
                    ],
                ],
            );

            $json = json_decode((string) $response->getBody(), true);

            $this->processZohoResponseError($json);
        }
    }
}
