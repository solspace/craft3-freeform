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

namespace Solspace\Freeform\Integrations\EmailMarketing\ActiveCampaign;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegration;

abstract class BaseActiveCampaignIntegration extends EmailMarketingIntegration implements ActiveCampaignIntegrationInterface
{
    public const CATEGORY_FIELD_VALUES = 'field-values';
    public const CATEGORY_TAGS = 'tags';
    public const CATEGORY_CONTACTS = 'contacts';
    public const CATEGORY_CONTACT_LISTS = 'contacts-lists';

    protected const LOG_CATEGORY = 'ActiveCampaign';

    protected const CATEGORY_CUSTOM = 'Custom';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API Token',
        instructions: 'Enter your API Token here.',
        order: 4,
    )]
    protected string $apiToken = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API URL',
        instructions: 'Enter your API specific URL (e.g. "https://youraccountname.api-us1.com" or "https://youraccountname.activehosted.com").',
        order: 5,
    )]
    protected string $apiUrl = '';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/lists?limit=100'));

        $json = json_decode((string) $response->getBody(), true);

        return !empty($json['lists']);
    }

    public function getApiToken(): string
    {
        return $this->getProcessedValue($this->apiToken);
    }

    public function fetchFields(ListObject $list, string $category, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/fields?limit=999'));
        $json = json_decode((string) $response->getBody());

        $fieldList = [];

        $fieldList[] = new FieldObject('firstName', 'First Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('lastName', 'Last Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone', 'Phone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('tags', 'Tags', FieldObject::TYPE_ARRAY, $category, false);

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

    public function fetchLists(Client $client): array
    {
        $lists = [];
        $offset = 0;
        $limit = 100;

        while (null !== $offset) {
            $response = $client->get($this->getEndpoint('/lists?limit='.$limit.'&offset='.$offset.'&orders[name]=ASC'));
            $json = json_decode((string) $response->getBody());

            $offset += $limit;

            $total = (int) $json->meta->total;
            if ($total <= $offset) {
                $offset = null;
            }

            if (isset($json->lists)) {
                foreach ($json->lists as $list) {
                    if (isset($list->id, $list->name)) {
                        $lists[] = new ListObject(
                            $list->id,
                            $list->name,
                        );
                    }
                }
            }
        }

        return $lists;
    }

    protected function getApiUrl(): string
    {
        return $this->getProcessedValue($this->apiUrl);
    }

    protected function getTagId(Client $client, string $name): null|int|string
    {
        static $tags;
        if (null === $tags) {
            $tags = [];
            $fetchedTags = $this->fetchTags($client);
            foreach ($fetchedTags as $tag) {
                if ('contact' !== $tag->tagType) {
                    continue;
                }

                $tags[$tag->id] = $tag->tag;
            }
        }

        foreach ($tags as $id => $tag) {
            if (strtolower($name) === strtolower($tag)) {
                return $id;
            }
        }

        $response = $client->post(
            $this->getEndpoint('/tags'),
            [
                'json' => [
                    'tag' => [
                        'tag' => $name,
                        'description' => '',
                        'tagType' => 'contact',
                    ],
                ],
            ],
        );

        $json = json_decode((string) $response->getBody());

        return $json->tag->id;
    }

    private function fetchTags(Client $client): array
    {
        $response = $client->get($this->getEndpoint('/tags'));

        $json = json_decode((string) $response->getBody());

        $tags = $json->tags;
        $tagsTotal = $json->meta->total;
        $tagsCount = \count($tags);
        $offset = $tagsCount;

        while ($tagsCount < $tagsTotal) {
            $response = $client->get($this->getEndpoint('/tags?offset='.$offset));

            $json = json_decode((string) $response->getBody());

            $count = \count($json->tags);
            $tagsCount += $count;
            $tags = array_merge($tags, $json->tags);
            $offset += $count;
        }

        return $tags;
    }
}
