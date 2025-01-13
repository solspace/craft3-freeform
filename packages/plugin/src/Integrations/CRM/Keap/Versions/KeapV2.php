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

namespace Solspace\Freeform\Integrations\CRM\Keap\Versions;

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
use Solspace\Freeform\Integrations\CRM\Keap\BaseKeapIntegration;

#[Type(
    name: 'Keap',
    type: Type::TYPE_CRM,
    version: 'v2',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class KeapV2 extends BaseKeapIntegration
{
    protected const API_VERSION = 'v2';

    // ==========================================
    //                Contact
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Contacts')]
    #[Input\Boolean(
        label: 'Map to Contacts',
        instructions: 'Should map to the Contacts endpoint.',
        order: 2,
    )]
    protected bool $mapContacts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapContacts)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Keap Contact fields.',
        order: 3,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CONTACT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $contactMapping = null;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Tags')]
    #[Input\Boolean(
        label: 'Map to Tags',
        instructions: 'Should map to the Tags endpoint.',
        order: 4,
    )]
    protected bool $mapTags = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mapTags)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Keap Tag fields.',
        order: 5,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_TAG,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $tagMapping = null;

    private ?int $contactId = null;

    public function getApiRootUrl(): string
    {
        $url = $this->getApiUrl();

        $url = rtrim($url, '/');

        return $url.'/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        $this->processContacts($form, $client);
        $this->processTags($form, $client);
    }

    private function processContacts(Form $form, Client $client): void
    {
        if (!$this->mapContacts) {
            $this->logger->debug('No Contacts mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
        if (!$mapping) {
            return;
        }

        // Infusionsoft wants custom fields in their own array
        $contact = [
            'custom_fields' => [],
        ];

        // Now we need to construct our array based on the Freeform field mapping handles
        $complexData = [];

        foreach ($mapping as $fieldName => $fieldValue) {
            // Deal with simple default - just rip off the fieldType (default:)
            if (str_starts_with($fieldName, 'default:')) {
                $processedFieldName = preg_replace('/^'.preg_quote('default:', '/').'/', '', $fieldName);

                $contact[$processedFieldName] = $fieldValue;
            }

            // Custom fields, we rip off the fieldHandle and insert as a 2d array into $contact['custom-fields']
            if (str_starts_with($fieldName, 'custom_field:')) {
                $processedFieldName = preg_replace('/^'.preg_quote('custom_field:', '/').'/', '', $fieldName);

                $contact['custom_fields'][] = [
                    'content' => $fieldValue,
                    'id' => $processedFieldName,
                ];
            }

            // If it's not custom_field or default, it means it's a 2d field (handle and sub-properties)
            $fieldScopes = explode(':', $fieldName, 3);
            // Double-check we have 3 items between the colons
            if (3 === \count($fieldScopes)) {
                // I.E. email_addresses
                $fieldGroup = $fieldScopes[0];
                // I.E. BILLING
                $fieldHandle = $fieldScopes[1];
                // I.E. country_code
                $fieldParameter = $fieldScopes[2];

                // If we haven't started processing this field handle yet, create an array for it so we don't lose existing properties

                // Check the type
                if (!isset($complexData[$fieldGroup])) {
                    $complexData[$fieldGroup] = [];
                }

                // Check the field handle and set the key
                if (!isset($complexData[$fieldGroup][$fieldHandle])) {
                    $complexData[$fieldGroup][$fieldHandle] = [];
                }

                // Construct our field property object
                $complexData[$fieldGroup][$fieldHandle][$fieldParameter] = $fieldValue;
            }
        }

        // This code will flatten the field name key to a value
        $flattenedData = [];
        foreach ($complexData as $fieldGroupHandle => $complexDatum) {
            $key = ('social_accounts' === $fieldGroupHandle) ? 'type' : 'field';
            $flattenedData[$fieldGroupHandle] = [];
            foreach ($complexDatum as $field => $item) {
                $item[$key] = $field;
                $flattenedData[$fieldGroupHandle][] = $item;
            }
        }

        $contact = array_merge($contact, $flattenedData);

        if (empty($contact['source_type'])) {
            $contact['source_type'] = 'WEBFORM';
        }

        if (empty($contact['contact_type'])) {
            $contact['contact_type'] = 'Lead';
        }

        if (!empty($contact['company'])) {
            $contact['company'] = $this->getOrCreateCompany(trim($contact['company']), $client);
        }

        // Now create our contact
        $response = $client->post(
            $this->getEndpoint('/contacts'),
            ['json' => $contact],
        );

        $json = json_decode((string) $response->getBody());
        if ($json && !empty($json->id)) {
            $this->contactId = $json->id;
        }

        $this->logger->info('Contact created', ['id' => $json?->id]);
        $this->logger->debug('With Mapping', $mapping);

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
    }

    private function processTags(Form $form, Client $client): void
    {
        if (!$this->mapTags) {
            $this->logger->debug('No Tags mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->tagMapping, self::CATEGORY_TAG);
        if (!$mapping) {
            return;
        }

        foreach ($mapping as $tag) {
            if (\is_array($tag)) {
                foreach ($tag as $subTag) {
                    $this->processTag($subTag, $client);
                }
            } else {
                $this->processTag($tag, $client);
            }
        }
    }

    private function getOrCreateCompany(string $companyName, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/companies?filter=company_name=='.$companyName));

        $json = json_decode((string) $response->getBody());
        if ($json && !empty($json->companies)) {
            $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);

            return (array) $json->companies[0];
        }

        // Company doesnt exist so create it
        $company = [
            'company_name' => $companyName,
        ];

        $response = $client->post(
            $this->getEndpoint('/companies'),
            ['json' => $company],
        );

        $json = json_decode((string) $response->getBody());
        if ($json && !empty($json->id)) {
            $company = [
                'id' => $json->id,
            ];

            $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
        }

        return $company;
    }

    private function getOrCreateTag(string $tagName, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/tags?filter=name=='.$tagName));

        $json = json_decode((string) $response->getBody());
        if ($json && !empty($json->tags)) {
            $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);

            return (array) $json->tags[0];
        }

        // Tag doesnt exist so create it
        $tag = [
            'name' => $tagName,
        ];

        $response = $client->post(
            $this->getEndpoint('/tags'),
            ['json' => $tag],
        );

        $json = json_decode((string) $response->getBody());
        if ($json && !empty($json->id)) {
            $tag = [
                'id' => $json->id,
            ];

            $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
        }

        return $tag;
    }

    private function processTag(string $tag, Client $client): void
    {
        $tag = $this->getOrCreateTag($tag, $client);

        if (!empty($this->contactId)) {
            // We need to link tags to contacts separately
            $this->applyTagToContact($tag['id'], $this->contactId, $client);
        }
    }

    private function applyTagToContact(int $tagId, int $contactId, Client $client): void
    {
        $response = $client->post(
            $this->getEndpoint('/tags/'.$tagId.'/contacts:applyTags'),
            [
                'json' => [
                    'contact_ids' => [
                        $contactId,
                    ],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
    }
}
