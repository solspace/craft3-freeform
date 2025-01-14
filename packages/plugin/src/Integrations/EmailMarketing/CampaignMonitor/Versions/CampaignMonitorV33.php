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

namespace Solspace\Freeform\Integrations\EmailMarketing\CampaignMonitor\Versions;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\CampaignMonitor\BaseCampaignMonitorIntegration;

#[Type(
    name: 'Campaign Monitor',
    type: Type::TYPE_EMAIL_MARKETING,
    version: 'v3.3',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class CampaignMonitorV33 extends BaseCampaignMonitorIntegration
{
    protected const API_VERSION = 'v3.3';

    // ==========================================
    //                   Custom
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Custom Fields',
        instructions: 'Select the Freeform fields to be mapped to the applicable Campaign Monitor Custom fields.',
        order: 6,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_CUSTOM,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $customMapping = null;

    public function getAuthorizeUrl(): string
    {
        return 'https://api.createsend.com/oauth';
    }

    public function getAccessTokenUrl(): string
    {
        return 'https://api.createsend.com/oauth/token';
    }

    public function getApiRootUrl(): string
    {
        $url = 'https://api.createsend.com';
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
            $this->logger->debug('Opt-in field used but not chosen. Skipping.');
            if (!$optInValue) {
                return;
            }
        }

        $email = $form->get($this->emailField->getUid())->getValue();
        if (!$email) {
            $this->logger->debug('Email field empty. Skipping.');

            return;
        }

        $email = strtolower($email);
        $customFields = [];

        $mapping = $this->processMapping($form, $this->customMapping, self::CATEGORY_CUSTOM);
        foreach ($mapping as $key => $value) {
            if ('Name' === $key) {
                continue;
            }

            if (\is_array($value)) {
                foreach ($value as $subValue) {
                    $customFields[] = [
                        'Key' => $key,
                        'Value' => $subValue,
                    ];
                }
            } else {
                $customFields[] = [
                    'Key' => $key,
                    'Value' => $value,
                ];
            }
        }

        $response = $client->post(
            $this->getEndpoint('/subscribers/'.$listId.'.json'),
            [
                'json' => [
                    'EmailAddress' => $email,
                    'Name' => $mapping['Name'] ?? '',
                    'CustomFields' => $customFields,
                    'Resubscribe' => true,
                    'RestartSubscriptionBasedAutoresponders' => true,
                    'ConsentToTrack' => 'Yes',
                    'ConsentToSendSms' => 'Yes',
                ],
            ],
        );

        $this->logger->info('New Contact created', ['email' => $email]);

        $this->triggerAfterResponseEvent(self::CATEGORY_CUSTOM, $response);
    }
}
