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

namespace Solspace\Freeform\Integrations\EmailMarketing\ActiveCampaign\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\ActiveCampaign\BaseActiveCampaignIntegration;

#[Type(
    name: 'ActiveCampaign',
    type: Type::TYPE_EMAIL_MARKETING,
    version: 'v3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class ActiveCampaignV3 extends BaseActiveCampaignIntegration
{
    protected const API_VERSION = '3';

    // ==========================================
    //                Custom
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Custom Fields',
        instructions: 'Select the Freeform fields to be mapped to the applicable ActiveCampaign Custom fields',
        order: 6,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_CUSTOM,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $customMapping = null;

    public function getApiRootUrl(): string
    {
        $url = $this->getApiUrl();

        $url = rtrim($url, '/');

        return $url.'/api/'.self::API_VERSION;
    }

    public function push(Form $form, Client $client): void
    {
        if (!$this->mailingList || !$this->emailField) {
            $this->logger->debug('Mailing list or email field not set. Skipping.');

            return;
        }

        $listId = $this->mailingList->getResourceId();
        if (!$listId) {
            $this->logger->debug('Mailing list ID not set. Skipping.');

            return;
        }

        if ($this->optInField) {
            $optInValue = $form->get($this->optInField->getUid())->getValue();
            if (!$optInValue) {
                $this->logger->debug('Opt-in field used but not chosen. Skipping.');

                return;
            }
        }

        $email = $form->get($this->emailField->getUid())->getValue();
        if (!$email) {
            $this->logger->debug('Email field empty. Skipping.');

            return;
        }

        $tags = [];

        $mapping = $this->processMapping($form, $this->customMapping, self::CATEGORY_CUSTOM);

        if (!empty($mapping['tags'])) {
            if (!\is_array($mapping['tags'])) {
                $mapping['tags'] = [$mapping['tags']];
            }

            foreach ($mapping['tags'] as $tag) {
                $tags = array_merge($tags, explode(';', $tag));
            }

            $tags = array_map('trim', $tags);

            unset($mapping['tags']);
        }

        $mapping['email'] = $email;

        $response = $client->post(
            $this->getEndpoint('/contact/sync'),
            [
                'json' => [
                    'contact' => $mapping,
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACTS, $response);

        $json = json_decode((string) $response->getBody());

        $contactId = $json->contact->id;
        $this->logger->info('New Contact created', ['id' => $contactId]);
        $this->logger->debug('With Mapping', $mapping);

        unset($mapping['firstName'], $mapping['lastName'], $mapping['phone']);

        $response = $client->post(
            $this->getEndpoint('/contactLists'),
            [
                'json' => [
                    'contactList' => [
                        'status' => 1,
                        'list' => $listId,
                        'contact' => $contactId,
                    ],
                ],
            ],
        );

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT_LISTS, $response);

        foreach ($mapping as $key => $value) {
            if (!is_numeric($key)) {
                continue;
            }

            $fieldId = (string) $key;

            if (\is_array($value)) {
                $value = '||'.implode('||', $value).'||';
            }

            $response = $client->post(
                $this->getEndpoint('/fieldValues'),
                [
                    'json' => [
                        'fieldValue' => [
                            'value' => $value,
                            'field' => $fieldId,
                            'contact' => $contactId,
                        ],
                    ],
                ],
            );

            $this->triggerAfterResponseEvent(self::CATEGORY_FIELD_VALUES, $response);
        }

        if ($contactId && $tags) {
            foreach ($tags as $tag) {
                $tagId = $this->getTagId($client, $tag);

                if ($tagId) {
                    $response = $client->post(
                        $this->getEndpoint('/contactTags'),
                        [
                            'json' => [
                                'contactTag' => [
                                    'tag' => $tagId,
                                    'contact' => $contactId,
                                ],
                            ],
                        ],
                    );

                    $this->triggerAfterResponseEvent(self::CATEGORY_TAGS, $response);
                }
            }
        }
    }
}
