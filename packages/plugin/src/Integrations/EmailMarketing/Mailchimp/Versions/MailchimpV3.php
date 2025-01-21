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

namespace Solspace\Freeform\Integrations\EmailMarketing\Mailchimp\Versions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\Mailchimp\BaseMailchimpIntegration;

#[Type(
    name: 'Mailchimp',
    type: Type::TYPE_EMAIL_MARKETING,
    version: 'v3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.png',
)]
class MailchimpV3 extends BaseMailchimpIntegration
{
    protected const API_VERSION = '3.0';

    // ==========================================
    //                   Contact
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Contact Fields',
        instructions: 'Select the Freeform fields to be mapped to the applicable Mailchimp Contact fields.',
        order: 6,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_CONTACT,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $contactMapping = null;

    // ==========================================
    //                   GDPR
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Marketing Permissions',
        instructions: 'Select the Freeform fields to be mapped to the applicable Mailchimp GDPR marketing permission fields.',
        order: 7,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_GDPR,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $gdprMapping = null;

    // ==========================================
    //                   Tag
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Contact Tags',
        instructions: 'Select the Freeform fields to be mapped to the applicable Mailchimp Tags',
        order: 8,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_TAG,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $tagMapping = null;

    // ==========================================
    //                   Group
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Contact Groups',
        instructions: 'Select the Freeform fields to be mapped to the applicable Mailchimp Groups',
        order: 9,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_GROUP,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $groupMapping = null;

    public function getAuthorizeUrl(): string
    {
        return 'https://login.mailchimp.com/oauth2/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://login.mailchimp.com/oauth2/token';
    }

    public function getApiRootUrl(): string
    {
        $url = 'https://us6.api.mailchimp.com';

        $dataCenter = $this->getDataCenter();
        if ($dataCenter) {
            $url = 'https://'.$dataCenter.'.api.mailchimp.com';
        }

        $url = rtrim($url, '/');

        return $url.'/'.self::API_VERSION;
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

        $email = strtolower($email);
        $emailHash = md5($email);

        $isDoubleOptIn = $this->isDoubleOptIn();

        $memberData = [
            'email_address' => $email,
            'status' => $isDoubleOptIn ? 'pending' : 'subscribed',
            'status_if_new' => $isDoubleOptIn ? 'pending' : 'subscribed',
        ];

        $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);

        $marketingPermissions = [];
        $gdprMapping = $this->processMapping($form, $this->gdprMapping, self::CATEGORY_GDPR);
        foreach ($gdprMapping as $key => $value) {
            $marketingPermissions[] = [
                'marketing_permission_id' => $key,
                'enabled' => !empty($value),
            ];
        }

        $tagMapping = $this->processMapping($form, $this->tagMapping, self::CATEGORY_TAG);
        $tags = reset($tagMapping);
        if ($tags) {
            if (\is_string($tags)) {
                $tags = explode(',', $tags);
            }

            $tags = array_map('trim', $tags);
            $tags = array_filter($tags);
        } else {
            $tags = [];
        }

        $interests = [];
        $groupMapping = $this->processMapping($form, $this->groupMapping, self::CATEGORY_GROUP);
        $groups = reset($groupMapping);
        if ($groups) {
            if (\is_string($groups)) {
                $groups = explode(',', $groups);
            }

            $groups = array_map('trim', $groups);
            $groups = array_filter($groups);

            foreach ($groups as $interest) {
                $interestId = $this->findInterestIdFromName($client, $interest);
                if ($interestId) {
                    $interests[$interestId] = true;
                }
            }
        }

        if (!empty($mapping)) {
            $memberData['merge_fields'] = $mapping;
        }

        if (!empty($marketingPermissions)) {
            $memberData['marketing_permissions'] = $marketingPermissions;
        }

        $interests = array_filter($interests);
        if (!empty($interests)) {
            $memberData['interests'] = $interests;
        }

        try {
            $response = $client->put(
                $this->getEndpoint('/lists/'.$listId.'/members/'.$emailHash),
                ['json' => $memberData],
            );

            $this->logger->info('New Contact created', ['email' => $email]);
            $this->logger->debug('With Mapping', $memberData);

            $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
        } catch (RequestException $exception) {
            $json = json_decode($exception->getResponse()->getBody());

            $is400 = isset($json->status) && 400 === $json->status;

            $isComplianceState = isset($json->title) && 'member in compliance state' === strtolower($json->title);

            if ($is400 && $isComplianceState) {
                $memberData['status'] = 'pending';

                $response = $client->put(
                    $this->getEndpoint('/lists/'.$listId.'/members/'.$emailHash),
                    ['json' => $memberData],
                );

                $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
            } else {
                throw $exception;
            }
        }

        $this->manageTags($client, $listId, $email, $tags);
    }
}
