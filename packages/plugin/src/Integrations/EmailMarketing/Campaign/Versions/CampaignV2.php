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

namespace Solspace\Freeform\Integrations\EmailMarketing\Campaign\Versions;

use GuzzleHttp\Client;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\PendingContactModel;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\EmailMarketing\Campaign\BaseCampaignIntegration;

#[Type(
    name: 'Campaign',
    type: Type::TYPE_EMAIL_MARKETING,
    version: 'v2',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class CampaignV2 extends BaseCampaignIntegration
{
    // ==========================================
    //                  Custom
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('Boolean(values.mailingList)')]
    #[Input\Special\Properties\FieldMapping(
        label: 'Custom Fields',
        instructions: 'Select the Freeform fields to be mapped to the applicable Campaign Custom fields.',
        order: 4,
        source: 'api/integrations/email-marketing/fields/'.self::CATEGORY_CUSTOM,
        parameterFields: [
            'id' => 'id',
            'values.mailingList' => 'mailingListId',
        ],
    )]
    protected ?FieldMapping $customMapping = null;

    public function getApiRootUrl(): string
    {
        return '';
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

        $mailingListElement = MailingListElement::find()->site('*')->id($listId)->one();
        if (!$mailingListElement) {
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

        $source = \Craft::$app->getRequest()->getReferrer();

        $mapping = $this->processMapping($form, $this->customMapping, self::CATEGORY_CUSTOM);

        if (method_exists(Campaign::$plugin->forms, 'createAndSubscribeContact')) {
            Campaign::$plugin->forms->createAndSubscribeContact(
                $email,
                $mapping,
                $mailingListElement,
                'Freeform',
                $source,
            );

            $this->logger->info('New Contact created and subscribed', ['email' => $email]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            $contact = Campaign::$plugin->contacts->getContactByEmail($email);
            if (null === $contact) {
                $contact = new ContactElement();
                $contact->email = $email;
            }

            $contact->setFieldValues($mapping);

            if ($mailingListElement->getMailingListType()->subscribeVerificationRequired) {
                $pendingContact = new PendingContactModel();
                $pendingContact->pid = StringHelper::uniqueId('p');
                $pendingContact->email = $email;
                $pendingContact->mailingListId = $mailingListElement->id;
                $pendingContact->source = $source;
                $pendingContact->fieldData = $contact->getSerializedFieldValues();

                if (Campaign::$plugin->pendingContacts->savePendingContact($pendingContact)) {
                    Campaign::$plugin->forms->sendVerifySubscribeEmail(
                        $pendingContact,
                        $mailingListElement,
                    );

                    $this->logger->info('New Contact created and verification email sent', ['email' => $email]);
                    $this->logger->debug('With Mapping', $mapping);
                }
            } elseif (\Craft::$app->getElements()->saveElement($contact)) {
                Campaign::$plugin->forms->subscribeContact(
                    $contact,
                    $mailingListElement,
                    'Freeform',
                    $source,
                );

                $this->logger->info('New Contact created and subscribed', ['email' => $email]);
                $this->logger->debug('With Mapping', $mapping);
            }
        }
    }
}
