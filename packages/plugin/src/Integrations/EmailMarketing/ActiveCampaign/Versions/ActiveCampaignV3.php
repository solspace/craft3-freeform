<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2022, Solspace, Inc.
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
use Solspace\Freeform\Events\Integrations\IntegrationResponseEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\ActiveCampaign\BaseActiveCampaignIntegration;
use yii\base\Event;

#[Type(
    name: 'ActiveCampaign (v3)',
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
            return;
        }

        $listId = $this->mailingList->getResourceId();
        if (!$listId) {
            return;
        }

        if ($this->optInField) {
            $optInValue = $form->get($this->optInField->getUid())->getValue();
            if (!$optInValue) {
                return;
            }
        }

        $email = $form->get($this->emailField->getUid())->getValue();
        if (!$email) {
            return;
        }

        $tags = [];

        $mapping = $this->processMapping($form, $this->customMapping, self::CATEGORY_CUSTOM);
        $newMapping = [];
        foreach ($mapping as $key => $value) {
            $key = str_replace('custom__', '', $key);
            $value = str_replace('custom__', '', $value);

            $newMapping[$key] = $value;
        }
        $mapping = $newMapping;

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

        try {
            $response = $client->post(
                $this->getEndpoint('/contact/sync'),
                [
                    'json' => [
                        'contact' => $mapping,
                    ],
                ],
            );

            Event::trigger(
                $this,
                self::EVENT_AFTER_RESPONSE,
                new IntegrationResponseEvent($this, self::CATEGORY_CUSTOM, $response)
            );

            $json = json_decode((string) $response->getBody());

            $contactId = $json->contact->id;

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

            Event::trigger(
                $this,
                self::EVENT_AFTER_RESPONSE,
                new IntegrationResponseEvent($this, self::CATEGORY_CUSTOM, $response)
            );

            foreach ($mapping as $key => $value) {
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

                Event::trigger(
                    $this,
                    self::EVENT_AFTER_RESPONSE,
                    new IntegrationResponseEvent($this, self::CATEGORY_CUSTOM, $response)
                );
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

                        Event::trigger(
                            $this,
                            self::EVENT_AFTER_RESPONSE,
                            new IntegrationResponseEvent($this, self::CATEGORY_CUSTOM, $response)
                        );
                    }
                }
            }
        } catch (\Exception $exception) {
            $this->processException($exception, self::LOG_CATEGORY);
        }
    }
}
