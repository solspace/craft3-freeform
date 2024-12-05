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

namespace Solspace\Freeform\Integrations\EmailMarketing\Dotdigital;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegration;

abstract class BaseDotdigitalIntegration extends EmailMarketingIntegration implements DotdigitalIntegrationInterface
{
    public const CATEGORY_CONTACT_DATA = 'data-fields';

    protected const LOG_CATEGORY = 'Dotdigital';

    #[Flag(self::FLAG_INTERNAL)]
    #[Input\Hidden]
    protected string $apiUrl = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API User Email',
        instructions: 'Enter your Dotdigital API user email',
        order: 2,
    )]
    protected string $apiUserEmail = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Validators\Required]
    #[Input\Text(
        label: 'API User Password',
        instructions: 'Enter your Dotdigital API user password',
        order: 3,
    )]
    protected string $apiUserPassword = '';

    #[Input\Select(
        label: 'Email Opt In Type',
        instructions: "Setting this to 'Verified Double' will result in a double opt-in confirmation email sent to the contact. The result will state that the contact's Opt-In Type is 'Double' and the Status is 'Pending Opt-In'. These will only update to 'Verified Double' and 'Subscribed', respectively, once the contact has clicked the link in the confirmation email, at which point they will be added to the account.",
        order: 4,
        options: [
            'Single' => 'Single',
            'Double' => 'Double',
            'VerifiedDouble' => 'Verified Double',
        ],
    )]
    protected string $optInType = '';

    #[Input\Select(
        label: 'Email Type',
        order: 5,
        options: [
            'PlainText' => 'Plain Text',
            'Html' => 'HTML',
        ],
    )]
    protected string $emailType = '';

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/account-info'));

        return 200 === $response->getStatusCode();
    }

    public function getApiUrl(): string
    {
        return $this->getProcessedValue($this->apiUrl);
    }

    public function setApiUrl(string $apiUrl): self
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    public function getApiUserEmail(): string
    {
        return $this->getProcessedValue($this->apiUserEmail);
    }

    public function getApiUserPassword(): string
    {
        return $this->getProcessedValue($this->apiUserPassword);
    }

    public function getOptInType(): string
    {
        return $this->getProcessedValue($this->optInType);
    }

    public function getEmailType(): string
    {
        return $this->getProcessedValue($this->emailType);
    }

    public function fetchFields(ListObject $list, string $category, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/data-fields'));
        $json = json_decode((string) $response->getBody());

        $fieldList = [];

        if (!empty($json)) {
            foreach ($json as $field) {
                $type = match ($field->type) {
                    'Boolean' => FieldObject::TYPE_BOOLEAN,
                    'Numeric' => FieldObject::TYPE_NUMERIC,
                    default => FieldObject::TYPE_STRING,
                };

                $fieldList[] = new FieldObject(
                    $field->name,
                    $field->name,
                    $type,
                    $category,
                    false,
                );
            }
        }

        return $fieldList;
    }

    public function fetchLists(Client $client): array
    {
        $response = $client->get($this->getEndpoint('/address-books'));
        $json = json_decode((string) $response->getBody());

        $lists = [];

        if (!empty($json)) {
            foreach ($json as $list) {
                if (isset($list->id, $list->name)) {
                    $lists[] = new ListObject(
                        $list->id,
                        $list->name,
                    );
                }
            }
        }

        return $lists;
    }
}
