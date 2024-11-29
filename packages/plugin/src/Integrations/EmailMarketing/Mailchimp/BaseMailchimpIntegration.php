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

namespace Solspace\Freeform\Integrations\EmailMarketing\Mailchimp;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2ConnectorInterface;
use Solspace\Freeform\Library\Integrations\OAuth\OAuth2Trait;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegration;

abstract class BaseMailchimpIntegration extends EmailMarketingIntegration implements OAuth2ConnectorInterface, MailchimpIntegrationInterface
{
    use OAuth2Trait;

    protected const LOG_CATEGORY = 'Mailchimp';

    protected const CATEGORY_CONTACT = 'Contact';

    protected const CATEGORY_GDPR = 'GDPR';

    protected const CATEGORY_TAG = 'Tag';

    protected const CATEGORY_GROUP = 'Group';

    #[Flag(self::FLAG_INTERNAL)]
    #[Input\Text]
    protected string $dataCenter = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Boolean(
        label: 'Use Double Opt-in',
        order: 4,
    )]
    protected bool $doubleOptIn = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Boolean(
        label: 'Append Tags on Update Instead of Overwriting',
        instructions: 'When updating an existing contact in Mailchimp, have new Contact Tags added to existing ones instead of overwriting them.',
        order: 5,
    )]
    protected bool $appendContactTags = false;

    private array $existingTags = [];

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/'));

        $json = json_decode((string) $response->getBody());

        return !empty($json->account_id);
    }

    public function getDataCenter(): string
    {
        return $this->dataCenter;
    }

    public function setDataCenter(string $dataCenter): self
    {
        $this->dataCenter = $dataCenter;

        return $this;
    }

    public function fetchFields(ListObject $list, string $category, Client $client): array
    {
        $listId = $list->getResourceId();

        return match ($category) {
            self::CATEGORY_CONTACT => $this->fetchContactFields($client, $listId, $category),
            self::CATEGORY_GDPR => $this->fetchGDPRFields($client, $listId, $category),
            self::CATEGORY_TAG => $this->fetchTagFields($category),
            self::CATEGORY_GROUP => $this->fetchGroupFields($category),
            default => [],
        };
    }

    public function fetchLists(Client $client): array
    {
        $response = $client->get(
            $this->getEndpoint('/lists'),
            [
                'query' => [
                    'fields' => 'lists.id,lists.name,lists.stats.member_count',
                    'count' => 999,
                ],
            ]
        );

        $json = json_decode((string) $response->getBody());

        $lists = [];

        if (isset($json->lists)) {
            foreach ($json->lists as $list) {
                if (isset($list->id, $list->name)) {
                    $lists[] = new ListObject(
                        $list->id,
                        $list->name,
                        $list->stats->member_count
                    );
                }
            }
        }

        return $lists;
    }

    protected function isDoubleOptIn(): bool
    {
        return $this->getProcessedValue($this->doubleOptIn);
    }

    protected function appendContactTags(): bool
    {
        return $this->getProcessedValue($this->appendContactTags);
    }

    protected function fetchInterestGroups(Client $client): array
    {
        static $fetchedInterestGroups;

        if ($fetchedInterestGroups) {
            return $fetchedInterestGroups;
        }

        $listId = $this->mailingList->getResourceId();

        $response = $client->get(
            $this->getEndpoint('/lists/'.$listId.'/interest-categories?fields=interest-categories.id,interest-categories.name'),
            [
                'query' => [
                    'count' => 999,
                ],
            ],
        );

        $json = json_decode((string) $response->getBody());

        $interestGroups = [];
        if (isset($json->categories)) {
            foreach ($json->categories as $interestGroup) {
                if (isset($interestGroup->id, $interestGroup->title)) {
                    $interestGroups[] = [
                        'id' => $interestGroup->id,
                        'name' => $interestGroup->title,
                    ];
                }
            }
        }

        $fetchedInterestGroups = $interestGroups;

        return $fetchedInterestGroups;
    }

    protected function fetchInterests(Client $client, string $interestGroup): array
    {
        $listId = $this->mailingList->getResourceId();

        $response = $client->get(
            $this->getEndpoint('/lists/'.$listId.'/interest-categories/'.$interestGroup.'/interests?fields=interests.id,interests.name'),
            [
                'query' => [
                    'count' => 999,
                ],
            ],
        );

        $json = json_decode((string) $response->getBody());

        $interests = [];
        if (isset($json->interests)) {
            foreach ($json->interests as $interest) {
                if (isset($interest->id, $interest->name)) {
                    $interests[] = [
                        'id' => $interest->id,
                        'name' => $interest->name,
                    ];
                }
            }
        }

        return $interests;
    }

    protected function manageTags(Client $client, string $listId, string $email, array $tags): void
    {
        $appendContactTags = $this->appendContactTags();

        $emailHash = md5(strtolower($email));

        try {
            $response = $client->get(
                $this->getEndpoint('/lists/'.$listId.'/members/'.$emailHash.'/tags'),
                [
                    'query' => [
                        'count' => 999,
                    ],
                ],
            );

            $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
        } catch (RequestException $exception) {
            throw $exception;
        }

        $json = json_decode((string) $response->getBody());

        $memberTags = [];
        foreach ($json->tags as $tag) {
            $memberTags[$tag->id] = $tag->name;
        }

        $tagsToAdd = array_diff($tags, $memberTags);

        $this->addTagsForMember($client, $listId, $email, $tagsToAdd);

        if (!$appendContactTags) {
            $tagsToDelete = array_diff($memberTags, $tags);

            $this->deleteTagsForMember($client, $listId, $emailHash, $tagsToDelete);
        }
    }

    protected function findInterestIdFromName(Client $client, string $name)
    {
        $interestGroups = $this->fetchInterestGroups($client);

        $interests = [];

        if (\count($interestGroups) > 0) {
            foreach ($interestGroups as $interestGroup) {
                $interests = array_merge($interests, $this->fetchInterests($client, $interestGroup['id']));
            }
        }

        foreach ($interests as $interest) {
            if (isset($interest['name']) && $interest['name'] === $name) {
                return $interest['id'];
            }
        }

        return null;
    }

    private function fetchTags(Client $client, string $listId): array
    {
        if ($this->existingTags) {
            return $this->existingTags;
        }

        try {
            $response = $client->get(
                $this->getEndpoint('/lists/'.$listId.'/segments'),
                [
                    'query' => [
                        'fields' => 'segments.id,segments.name',
                        'count' => 999,
                    ],
                ],
            );

            $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
        } catch (RequestException $exception) {
            throw $exception;
        }

        $json = json_decode((string) $response->getBody());

        foreach ($json->segments as $tag) {
            $this->existingTags[$tag->id] = strtolower($tag->name);
        }

        return $this->existingTags;
    }

    private function getOrCreateTag(Client $client, string $listId, string $tagName): null|int|string
    {
        $existingTags = $this->fetchTags($client, $listId);

        $tagNameLowerCase = strtolower($tagName);
        if (\in_array($tagNameLowerCase, $existingTags, true)) {
            return array_search($tagNameLowerCase, $existingTags, true);
        }

        try {
            $response = $client->post(
                $this->getEndpoint('/lists/'.$listId.'/segments'),
                [
                    'json' => [
                        'name' => $tagName,
                        'static_segment' => [],
                    ],
                ],
            );

            $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
        } catch (RequestException $exception) {
            throw $exception;
        }

        $json = json_decode((string) $response->getBody());

        return $json->id;
    }

    private function addTagsForMember(Client $client, string $listId, string $email, array $tags): void
    {
        foreach ($tags as $tag) {
            $tagId = $this->getOrCreateTag($client, $listId, $tag);

            try {
                $response = $client->post(
                    $this->getEndpoint('/lists/'.$listId.'/segments/'.$tagId.'/members'),
                    [
                        'json' => [
                            'email_address' => $email,
                        ],
                    ],
                );

                $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
            } catch (RequestException $exception) {
                throw $exception;
            }

            $json = json_decode((string) $response->getBody());
        }
    }

    private function deleteTagsForMember(Client $client, string $listId, string $emailHash, array $tagsToDelete): void
    {
        foreach ($tagsToDelete as $tagId => $tagName) {
            try {
                $response = $client->delete($this->getEndpoint('/lists/'.$listId.'/segments/'.$tagId.'/members/'.$emailHash));

                $this->triggerAfterResponseEvent(self::CATEGORY_TAG, $response);
            } catch (RequestException $exception) {
                throw $exception;
            }
        }
    }

    private function fetchContactFields(Client $client, string $listId, string $category): array
    {
        $response = $client->get(
            $this->getEndpoint('/lists/'.$listId.'/merge-fields'),
            [
                'query' => [
                    'count' => 999,
                ],
            ],
        );

        $json = json_decode((string) $response->getBody());

        if (!isset($json->merge_fields) || !$json->merge_fields) {
            throw new IntegrationException('Could not fetch fields for '.$category);
        }

        $fieldList = [];

        foreach ($json->merge_fields as $field) {
            $type = match ($field->type) {
                'text', 'website', 'url', 'dropdown', 'radio', 'date', 'birthday', 'zip' => FieldObject::TYPE_STRING,
                'number', 'phone' => FieldObject::TYPE_NUMERIC,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                $field->tag,
                $field->name,
                $type,
                $category,
                $field->required,
            );
        }

        return $fieldList;
    }

    private function fetchGDPRFields(Client $client, string $listId, string $category): array
    {
        $response = $client->get(
            $this->getEndpoint('/lists/'.$listId.'/members'),
            [
                'query' => [
                    'count' => 1,
                    'fields' => [
                        'members.id',
                        'members.marketing_permissions',
                    ],
                ],
            ],
        );

        $json = json_decode((string) $response->getBody());

        $fieldList = [];

        if (!\count($json->members)) {
            $tempResponse = $client->post(
                $this->getEndpoint('/lists/'.$listId.'/members'),
                [
                    'json' => [
                        'email_address' => rand(10000, 99999).'_temp@test.test',
                        'status' => 'subscribed',
                    ],
                ],
            );

            $tempJson = json_decode((string) $tempResponse->getBody());

            $marketingPermissions = $tempJson->marketing_permissions ?? [];

            $client->delete($this->getEndpoint('/lists/'.$listId.'/members/'.$tempJson->id));
        } else {
            $marketing = reset($json->members);

            $marketingPermissions = $marketing->marketing_permissions ?? [];
        }

        foreach ($marketingPermissions as $permission) {
            $fieldList[] = new FieldObject(
                $permission->marketing_permission_id,
                $permission->text,
                FieldObject::TYPE_BOOLEAN,
                $category,
                false,
            );
        }

        return $fieldList;
    }

    private function fetchTagFields(string $category): array
    {
        return [new FieldObject('tags', 'Tags', FieldObject::TYPE_STRING, $category, false)];
    }

    private function fetchGroupFields(string $category): array
    {
        return [new FieldObject('interests', 'Group or Interest', FieldObject::TYPE_STRING, $category, false)];
    }
}
