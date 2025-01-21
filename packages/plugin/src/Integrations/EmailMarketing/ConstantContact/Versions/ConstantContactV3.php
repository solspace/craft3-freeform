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

namespace Solspace\Freeform\Integrations\EmailMarketing\ConstantContact\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\ConstantContact\BaseConstantContactIntegration;

#[Type(
    name: 'Constant Contact',
    type: Type::TYPE_EMAIL_MARKETING,
    version: 'v3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class ConstantContactV3 extends BaseConstantContactIntegration
{
    protected const API_VERSION = 'v3';

    // ==========================================
    //               Contact Custom
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Contact Fields',
        instructions: 'Select the Freeform fields to be mapped to the applicable Constant Contact fields.',
        order: 4,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_CONTACT_CUSTOM,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $contactCustomMapping = null;

    public function getAuthorizeUrl(): string
    {
        return 'https://authz.constantcontact.com/oauth2/default/v1/authorize';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://authz.constantcontact.com/oauth2/default/v1/token';
    }

    public function getApiRootUrl(): string
    {
        $url = 'https://api.cc.email';
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
        $contactData = [];

        $mapping = $this->processMapping($form, $this->contactCustomMapping, self::CATEGORY_CONTACT_CUSTOM);
        foreach ($mapping as $key => $value) {
            if (preg_match('/^street_address_(.*)/', $key, $matches)) {
                if (empty($contactData['street_address'])) {
                    $contactData['street_address'] = [];
                }

                $contactData['street_address'][$matches[1]] = $value;
            } elseif (preg_match('/^custom_(.*)/', $key, $matches)) {
                if (empty($contactData['custom_fields'])) {
                    $contactData['custom_fields'] = [];
                }

                $contactData['custom_fields'][] = [
                    'custom_field_id' => $matches[1],
                    'value' => $value,
                ];
            } else {
                $contactData[$key] = $value;
            }
        }

        if (isset($contactData['street_address']) && empty($contactData['street_address']['kind'])) {
            $contactData['street_address']['kind'] = 'home';
        }

        $contactData = array_merge(
            [
                'email_address' => $email,
                'create_source' => 'Contact',
                'list_memberships' => [$listId],
            ],
            $contactData,
        );

        $response = $client->post(
            $this->getEndpoint('/contacts/sign_up_form'),
            ['json' => $contactData],
        );

        $this->logger->info('New Contact created', ['email' => $email]);
        $this->logger->debug('With Mapping', $contactData);

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT_CUSTOM, $response);
    }
}
