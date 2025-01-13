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

namespace Solspace\Freeform\Integrations\CRM\Freshdesk;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegration;

abstract class BaseFreshdeskIntegration extends CRMIntegration implements FreshdeskIntegrationInterface
{
    protected const LOG_CATEGORY = 'Freshdesk';

    protected const CATEGORY_TICKET = 'Ticket';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'API Key',
        instructions: 'Enter your API Key here.',
        order: 1,
    )]
    protected string $apiKey = '';

    #[Flag(self::FLAG_ENCRYPTED)]
    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        instructions: 'Enter your Domain (e.g. "https://yourcompany.freshdesk.com").',
        order: 2,
    )]
    protected string $domain = '';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Default Type (Optional)',
        instructions: "Set the default Type for tickets, e.g. 'Question'.",
        order: 3,
    )]
    protected ?string $defaultType = null;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Default Priority (Optional)',
        instructions: "Set the default Priority for tickets, e.g. '1' (Low), '2' (Medium), '3' (High), '4' (Urgent).",
        order: 4,
    )]
    protected ?string $defaultPriority = null;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Default Status (Optional)',
        instructions: "Set the default Status for tickets, e.g. '2' (Open), '3' (Pending), '4' (Resolved), '5' (Closed).",
        order: 5,
    )]
    protected ?string $defaultStatus = null;

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Default Source (Optional)',
        instructions: "Set the default Source for tickets, e.g. '1' (Email), '2' (Portal), '3' (Phone), '7' (Chat), '9' (Feedback Widget), '10' (Outbound Email).",
        order: 6,
    )]
    protected ?string $defaultSource = null;

    public function checkConnection(Client $client): bool
    {
        $response = $client->get($this->getEndpoint('/tickets'));

        return 200 === $response->getStatusCode();
    }

    public function getApiKey(): string
    {
        return $this->getProcessedValue($this->apiKey);
    }

    public function getDomain(): string
    {
        return $this->getProcessedValue($this->domain);
    }

    public function getDefaultType(): ?string
    {
        return $this->getProcessedValue($this->defaultType);
    }

    public function getDefaultPriority(): ?string
    {
        return $this->getProcessedValue($this->defaultPriority);
    }

    public function getDefaultStatus(): ?string
    {
        return $this->getProcessedValue($this->defaultStatus);
    }

    public function getDefaultSource(): ?string
    {
        return $this->getProcessedValue($this->defaultSource);
    }

    public function fetchFields(string $category, Client $client): array
    {
        $response = $client->get($this->getEndpoint('/admin/ticket_fields'));
        $json = json_decode((string) $response->getBody(), false);

        if (!isset($json) || !$json) {
            throw new IntegrationException('Could not fetch fields for '.$category);
        }

        $fieldList = [];

        $fieldList[] = new FieldObject('name', 'Name', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('email', 'Email', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('phone', 'Phone', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('unique_external_id', 'Unique External ID', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('subject', 'Subject', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('type', 'Type', FieldObject::TYPE_STRING, $category, false);
        $fieldList[] = new FieldObject('status', 'Status', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('priority', 'Priority', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('description', 'Description', FieldObject::TYPE_STRING, $category, true);
        $fieldList[] = new FieldObject('responder_id', 'Responder ID', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('attachments', 'Attachments', FieldObject::TYPE_ARRAY, $category, false);
        $fieldList[] = new FieldObject('cc_emails', 'CC Emails', FieldObject::TYPE_ARRAY, $category, false);
        $fieldList[] = new FieldObject('due_by', 'Due By', FieldObject::TYPE_DATETIME, $category, false);
        $fieldList[] = new FieldObject('email_config_id', 'Email Config ID', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('fr_due_by', 'First Response Due By', FieldObject::TYPE_DATETIME, $category, false);
        $fieldList[] = new FieldObject('group_id', 'Group ID', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('product_id', 'Product ID', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('source', 'Source', FieldObject::TYPE_NUMERIC, $category, false);
        $fieldList[] = new FieldObject('tags', 'Tags', FieldObject::TYPE_ARRAY, $category, false);
        $fieldList[] = new FieldObject('company_id', 'Company ID', FieldObject::TYPE_NUMERIC, $category, false);

        foreach ($json as $field) {
            if ($field->default) {
                continue;
            }

            $type = match ($field->type) {
                'custom_text', 'custom_dropdown', 'custom_paragraph' => FieldObject::TYPE_STRING,
                'custom_decimal', 'custom_number' => FieldObject::TYPE_NUMERIC,
                'custom_date' => FieldObject::TYPE_DATETIME,
                'custom_checkbox' => FieldObject::TYPE_BOOLEAN,
                default => null,
            };

            if (null === $type) {
                continue;
            }

            $fieldList[] = new FieldObject(
                $field->name,
                $field->label,
                $type,
                $category,
                $field->required_for_customers,
            );
        }

        return $fieldList;
    }
}
