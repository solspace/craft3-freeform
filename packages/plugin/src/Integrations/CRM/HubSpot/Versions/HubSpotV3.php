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

namespace Solspace\Freeform\Integrations\CRM\HubSpot\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Delimiter;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapItem;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\CRM\HubSpot\BaseHubSpotIntegration;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;

#[Type(
    name: 'HubSpot',
    type: Type::TYPE_CRM,
    version: 'v3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class HubSpotV3 extends BaseHubSpotIntegration
{
    // ==========================================
    //                  Deals
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
        instructions: 'Select the Freeform fields to be mapped to the applicable HubSpot Deal fields',
        order: 6,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_DEAL,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $dealMapping = null;

    // ==========================================
    //                 Contacts
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Contacts')]
    #[Input\Boolean(
        label: 'Map to Contacts',
        instructions: 'Should map to the Contacts endpoint.',
        order: 7,
    )]
    protected bool $mapContacts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapContacts)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable HubSpot Contacts fields',
        order: 8,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CONTACT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $contactMapping = null;

    // ==========================================
    //                Companies
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Companies')]
    #[Input\Boolean(
        label: 'Map to Companies',
        instructions: 'Should map to the Companies endpoint.',
        order: 9,
    )]
    protected bool $mapCompanies = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapCompanies)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable HubSpot Company fields',
        order: 10,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_COMPANY,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $companyMapping = null;

    private ?int $companyId = null;
    private ?int $contactId = null;
    private ?int $dealId = null;

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/objects/contacts'));

        $json = json_decode((string) $response->getBody(), false);

        return 200 === $response->getStatusCode() && isset($json->results);
    }

    public function fetchFields(string $category, Client $client): array
    {
        $record = $this->getRecord($category);

        $response = $client->get($this->getEndpoint('/properties/'.$record));
        $json = json_decode((string) $response->getBody());
        if (empty($json)) {
            throw new IntegrationException('Could not fetch fields for '.$category);
        }

        $fieldList = [];
        foreach ($json->results as $field) {
            $isReadOnly = $field->modificationMetadata->readOnlyValue;
            $isHidden = $field->hidden;
            $isCalculated = $field->calculated;

            if ($isReadOnly || $isHidden || $isCalculated) {
                continue;
            }

            $options = null;
            if (\count($field->options)) {
                foreach ($field->options as $option) {
                    $options[] = [
                        'key' => $option->value,
                        'label' => $option->label,
                    ];
                }
            }

            $type = match ($field->type) {
                'string', 'phone_number' => FieldObject::TYPE_STRING,
                'datetime', 'date' => FieldObject::TYPE_MICROTIME,
                'bool' => FieldObject::TYPE_BOOLEAN,
                'number' => FieldObject::TYPE_NUMERIC,
                'enumeration' => FieldObject::TYPE_ARRAY,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                $field->name,
                $field->label,
                $type,
                $category,
                false,
                $options,
            );
        }

        return $fieldList;
    }

    public function getApiRootUrl(): string
    {
        return 'https://api.hubapi.com/crm/v3';
    }

    public function getContactApiUrl(): string
    {
        return 'https://api.hubapi.com/contacts/v1';
    }

    public function push(Form $form, Client $client): void
    {
        $this->pushContacts($form, $client);
        $this->pushCompanies($form, $client);
        $this->pushDeals($form, $client);

        $this->createAssociations($form, $client);
    }

    private function pushContacts(Form $form, Client $client): void
    {
        if (!$this->mapContacts) {
            $this->logger->debug('No Contacts mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
        if (!$mapping) {
            return;
        }

        if ($this->getIpField() && isset($_SERVER['REMOTE_ADDR'])) {
            $mapping[$this->getIpField()] = $_SERVER['REMOTE_ADDR'];
        }

        $email = $this->getEmailFieldValue($mapping);

        $contactId = null;
        $contact = $this->searchForObject(
            $client,
            $this->getRecord(self::CATEGORY_CONTACT),
            ['email' => $email],
            $this->getMappedProps($this->contactMapping)
        );

        if (!$contact) {
            $this->logger->debug('No existing contact by email found', ['email' => $email]);

            $contactCookie = $_COOKIE['hubspotutk'] ?? null;
            if ($contactCookie) {
                $endpoint = \sprintf(
                    '%s/contact/utk/%s/profile',
                    $this->getContactApiUrl(),
                    $contactCookie
                );

                try {
                    $response = $client->get($endpoint);
                    $json = json_decode((string) $response->getBody());
                    $contactId = $json->vid ?? null;

                    $this->logger->debug('Found contact by cookie', ['cookie' => $_COOKIE['hubspotutk'], 'contactId' => $contactId]);
                } catch (\Exception $exception) {
                    $this->logger->debug('Failed to find contact by cookie', ['cookie' => $_COOKIE['hubspotutk']]);
                    $this->processException($exception, self::CATEGORY_CONTACT);
                }
            }
        } else {
            $contactId = $contact->id;
            $this->logger->debug('Found existing contact by email', ['email' => $email, 'contactId' => $contactId]);
        }

        if ($contactId) {
            if ($this->getAppendContactData()) {
                $mapping = $this->appendValues(
                    self::CATEGORY_CONTACT,
                    $mapping,
                    (array) $contact->properties
                );
            }

            $response = $client->patch(
                $this->getEndpoint('/objects/contacts/'.$contactId),
                ['json' => ['properties' => $mapping]],
            );

            $this->logger->info('Updated contact', ['contactId' => $contactId]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            [$response, $data] = $this->getJsonResponse(
                $client->post(
                    $this->getEndpoint('/objects/contacts'),
                    ['json' => ['properties' => $mapping]],
                )
            );

            $contactId = $data->id;
            $this->logger->info('New Contact created', ['contactId' => $contactId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
        $this->contactId = $contactId;
    }

    private function pushCompanies(Form $form, Client $client): void
    {
        if (!$this->mapCompanies) {
            $this->logger->debug('No Companies mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->companyMapping, self::CATEGORY_COMPANY);
        if (!$mapping) {
            return;
        }

        $domain = $this->getDomainFieldValue($mapping);
        if (!$domain) {
            $website = $mapping['website'] ?? null;
            if ($website) {
                $domain = $this->extractDomainFromUrl($website);
                $this->logger->debug('Extracted domain from website', ['website' => $website, 'domain' => $domain]);
            }

            if (!$domain) {
                $contactProps = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
                $email = $this->getEmailFieldValue($contactProps);

                if ($email) {
                    $domain = $this->extractDomainFromEmail($email);

                    if ($domain) {
                        $mapping['domain'] = $domain;
                        $this->logger->debug('Extracted domain from email', ['email' => $email, 'domain' => $domain]);
                    }
                }
            }
        }

        $company = $this->searchForObject(
            $client,
            $this->getRecord(self::CATEGORY_COMPANY),
            ['domain' => $domain],
            $this->getMappedProps($this->companyMapping)
        );

        if ($company) {
            $companyId = $company->id;

            // Prevent the customer from updating company name if it's an existing company
            unset($mapping['name']);

            if ($this->getAppendCompanyData()) {
                $mapping = $this->appendValues(
                    self::CATEGORY_COMPANY,
                    $mapping,
                    (array) $company->properties
                );
            }

            $response = $client->patch(
                $this->getEndpoint('/objects/companies/'.$companyId),
                ['json' => ['properties' => $mapping]],
            );

            $this->logger->info('Updated company', ['companyId' => $companyId]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            [$response, $data] = $this->getJsonResponse(
                $client->post(
                    $this->getEndpoint('/objects/companies'),
                    ['json' => ['properties' => $mapping]],
                )
            );

            $companyId = $data->id;

            $this->logger->info('New Company created', ['companyId' => $companyId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_COMPANY, $response);
        $this->companyId = $companyId;
    }

    private function pushDeals(Form $form, Client $client): void
    {
        if (!$this->mapDeals) {
            $this->logger->debug('No Deals mapped, skipping.');

            return;
        }

        $properties = $this->processMapping($form, $this->dealMapping, self::CATEGORY_DEAL);
        if (!$properties) {
            return;
        }

        [$response, $data] = $this->getJsonResponse(
            $client->post(
                $this->getEndpoint('/objects/deals'),
                ['json' => ['properties' => $properties]],
            )
        );

        $this->logger->info('New Deal created', ['dealId' => $data->id]);
        $this->logger->debug('With Mapping', $properties);

        $this->triggerAfterResponseEvent(self::CATEGORY_DEAL, $response);
        $this->dealId = $data->id;
    }

    private function createAssociations(Form $form, Client $client): void
    {
        $dealId = $this->dealId;
        $companyId = $this->companyId;
        $contactId = $this->contactId;

        $base = 'https://api.hubapi.com/crm/v4/objects';

        if ($companyId) {
            $endpoint = $base.'/company/'.$companyId.'/associations/default';
            if ($dealId) {
                $client->put($endpoint."/deal/{$dealId}");
                $this->logger->debug('Associated deal with company', ['companyId' => $companyId, 'dealId' => $dealId]);
            }

            if ($contactId) {
                $client->put($endpoint."/contact/{$contactId}");
                $this->logger->debug('Associated contact with company', ['companyId' => $companyId, 'contactId' => $contactId]);
            }
        }

        if ($contactId) {
            $endpoint = $base.'/contact/'.$contactId.'/associations/default';
            if ($companyId) {
                $client->put($endpoint."/company/{$companyId}");
                $this->logger->debug('Associated company with contact', ['contactId' => $contactId, 'companyId' => $companyId]);
            }

            if ($dealId) {
                $client->put($endpoint."/deal/{$dealId}");
                $this->logger->debug('Associated deal with contact', ['contactId' => $contactId, 'dealId' => $dealId]);
            }
        }

        if ($dealId) {
            $endpoint = $base.'/deal/'.$dealId.'/associations/default';
            if ($companyId) {
                $client->put($endpoint."/company/{$companyId}");
                $this->logger->debug('Associated company with deal', ['dealId' => $dealId, 'companyId' => $companyId]);
            }

            if ($contactId) {
                $client->put($endpoint."/contact/{$contactId}");
                $this->logger->debug('Associated contact with deal', ['dealId' => $dealId, 'contactId' => $contactId]);
            }
        }
    }

    private function getEmailFieldValue(array $properties): ?string
    {
        return $properties['email'] ?? null;
    }

    private function getDomainFieldValue(array $companyProps): ?string
    {
        return $companyProps['domain'] ?? null;
    }

    private function extractDomainFromEmail(string $email): ?string
    {
        if (preg_match('/^.*@([^@]+)$/', $email, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function extractDomainFromUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }

        $domain = str_ireplace('www.', '', parse_url($url, \PHP_URL_HOST));
        if (!$domain) {
            return null;
        }

        return $domain;
    }

    private function searchForObject(
        Client $client,
        string $record,
        array $searchMap,
        array $properties
    ): ?\stdClass {
        $filters = [];
        foreach ($searchMap as $key => $value) {
            if (!$value) {
                continue;
            }

            $filters[] = [
                'propertyName' => $key,
                'operator' => 'EQ',
                'value' => $value,
            ];
        }

        if (empty($filters)) {
            return null;
        }

        [, $data] = $this->getJsonResponse(
            $client->post(
                $this->getEndpoint("/objects/{$record}/search"),
                [
                    'json' => [
                        'properties' => $properties,
                        'filterGroups' => [['filters' => $filters]],
                        'limit' => 1,
                    ],
                ],
            )
        );

        if ($data->total > 0) {
            return $data->results[0];
        }

        return null;
    }

    private function appendValues(string $category, array $mapping, array $originalValues): array
    {
        $fieldDefinitions = $this->getProcessableFields($category);

        foreach ($mapping as $handle => $value) {
            $definition = $fieldDefinitions[$handle] ?? null;
            if (FieldObject::TYPE_ARRAY !== $definition?->getType()) {
                continue;
            }

            $original = explode(';', $originalValues[$handle] ?? '');

            $value = explode(';', $value);
            $value = array_merge($value, $original);
            $value = array_filter($value);
            $value = array_unique($value);
            $value = implode(';', $value);

            $mapping[$handle] = $value;
        }

        return $mapping;
    }

    private function getRecord(string $category): string
    {
        return match ($category) {
            self::CATEGORY_CONTACT => 'contacts',
            self::CATEGORY_COMPANY => 'companies',
            default => 'deals',
        };
    }

    private function getMappedProps(FieldMapping $mapping): array
    {
        return array_map(
            fn (FieldMapItem $item) => $item->getSource(),
            $mapping->getMapping()
        );
    }
}
