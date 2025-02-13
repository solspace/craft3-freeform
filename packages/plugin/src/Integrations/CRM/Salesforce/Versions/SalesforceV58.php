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

namespace Solspace\Freeform\Integrations\CRM\Salesforce\Versions;

use Carbon\Carbon;
use craft\elements\Asset;
use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Delimiter;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapItem;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Fields\Implementations\CheckboxesField;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\CRM\Salesforce\BaseSalesforceIntegration;
use Solspace\Freeform\Integrations\CRM\Salesforce\SalesforceIntegrationInterface;

#[Type(
    name: 'Salesforce',
    type: Type::TYPE_CRM,
    version: 'v58',
    readme: __DIR__.'/../README.md',
    iconPath: __DIR__.'/../icon.svg',
)]
class SalesforceV58 extends BaseSalesforceIntegration implements SalesforceIntegrationInterface
{
    protected const API_VERSION = 'v58.0';

    // ==========================================
    //                   Leads
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Leads')]
    #[Input\Boolean(
        label: 'Map to Leads',
        instructions: 'Map submission data to create Leads in Salesforce.',
        order: 4,
    )]
    protected bool $mapLeads = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[Input\Boolean(
        label: 'Update Duplicate Leads',
        instructions: 'Check existing Salesforce Lead records for a matching email address and update the record instead of creating a new one.',
        order: 5,
    )]
    protected bool $checkLeadDuplicates = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[Input\Boolean(
        label: 'Attach Uploaded Files to Leads',
        instructions: 'Send any uploaded files to Salesforce and relate them to the created Lead.',
        order: 5,
    )]
    protected bool $filesForLeads = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[Input\Boolean(
        label: 'Assign Lead Owner',
        instructions: 'Assign a lead owner based on lead owner assignment rules in Salesforce.',
        order: 6,
    )]
    protected bool $assignLeadOwner = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[Input\Boolean(
        label: 'Convert Leads to Contact Tasks for Returning Customers',
        instructions: 'If a Salesforce Contact with the same email exists, create a new Task for that Contact instead of a new Lead.',
        order: 7,
    )]
    protected bool $convertLeadsToTasks = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[VisibilityFilter('values.convertLeadsToTasks')]
    #[Input\Text(
        instructions: 'Enter the text you would like to have set for new Task subjects.',
        order: 8,
    )]
    protected string $taskSubject = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[VisibilityFilter('values.convertLeadsToTasks')]
    #[Input\Text(
        instructions: "Enter a relative textual date string for the Due Date of the newly created Task (e.g. '2 days').",
        order: 9,
    )]
    protected string $taskDueDate = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapLeads')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Salesforce Lead fields.',
        order: 10,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_LEAD,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $leadMapping = null;

    // ==========================================
    //               Opportunities
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Opportunities')]
    #[Input\Boolean(
        label: 'Map to Opportunities',
        instructions: 'Map submission data to create Opportunities in Salesforce.',
        order: 11,
    )]
    protected bool $mapOpportunities = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapOpportunities')]
    #[Input\Boolean(
        label: 'Attach Uploaded Files to Opportunities',
        instructions: 'Send any uploaded files to Salesforce and relate them to the created Opportunity.',
        order: 12,
    )]
    protected bool $filesForOpportunities = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapOpportunities')]
    #[Validators\Required]
    #[Input\Text(
        instructions: 'Enter a relative textual date string for the Close Date of the newly created Opportunity (e.g. \'7 days\').',
        order: 13,
    )]
    protected string $closeDate = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapOpportunities')]
    #[Validators\Required]
    #[Input\Text(
        label: 'Stage Name',
        instructions: 'Enter the Stage Name the newly created Opportunity should be assigned to (e.g. \'Prospecting\').',
        order: 14,
    )]
    protected string $stage = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapOpportunities')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Salesforce Opportunity fields.',
        order: 15,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_OPPORTUNITY,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $opportunityMapping = null;

    // ==========================================
    //                  Accounts
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Accounts')]
    #[Input\Boolean(
        label: 'Map to Accounts',
        instructions: 'Map submission data to create Accounts in Salesforce.',
        order: 16,
    )]
    protected bool $mapAccounts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapAccounts')]
    #[Input\Boolean(
        label: 'Attach Uploaded Files to Accounts',
        instructions: 'Send any uploaded files to Salesforce and relate them to the created Account.',
        order: 17,
    )]
    protected bool $filesForAccounts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapAccounts')]
    #[Input\Boolean(
        label: 'Append Checkboxes field values on Account update',
        instructions: 'If an Account already exists in Salesforce, enabling this option will add additional Checkboxes field values to the Account in Salesforce instead of replacing the existing options.',
        order: 18,
    )]
    protected bool $appendAccountData = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapAccounts')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Salesforce Contact fields.',
        order: 19,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_ACCOUNT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $accountMapping = null;

    // ==========================================
    //                  Contacts
    // ==========================================

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Delimiter('Contacts')]
    #[Input\Boolean(
        label: 'Map to Contacts',
        instructions: 'Map submission data to create Contacts in Salesforce.',
        order: 20,
    )]
    protected bool $mapContacts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapContacts')]
    #[Input\Boolean(
        label: 'Attach Uploaded Files to Contacts',
        instructions: 'Send any uploaded files to Salesforce and relate them to the created Contact.',
        order: 21,
    )]
    protected bool $filesForContacts = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapContacts')]
    #[Input\Boolean(
        label: 'Check Contact email address and Account website when checking for Duplicates',
        instructions: "By default, Freeform checks the Contact's first name, last name, email address, and Account name. If enabled, it will check only the Contact's email address and the Account's website. If no website is provided, Freeform will use the domain from the Contact's email address.",
        order: 22,
    )]
    protected bool $duplicateCheck = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapContacts')]
    #[Input\Boolean(
        label: 'Append Checkboxes field values on Contact update',
        instructions: 'If a Contact already exists in Salesforce, enabling this option will add additional Checkboxes field values to the Contact in Salesforce instead of replacing the existing options.',
        order: 23,
    )]
    protected bool $appendContactData = false;

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[VisibilityFilter('values.mapContacts')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Salesforce Contact fields.',
        order: 24,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CONTACT,
        parameterFields: ['id' => 'id'],
    )]
    protected ?FieldMapping $contactMapping = null;

    private ?string $accountId = null;
    private ?array $linkedContentDocumentIds = null;

    public function getApiRootUrl(): string
    {
        return $this->getInstanceUrl().'/services/data/'.self::API_VERSION;
    }

    public function getTaskSubject(): string
    {
        return $this->getProcessedValue($this->taskSubject);
    }

    public function getTaskDueDate(): string
    {
        return $this->getProcessedValue($this->taskDueDate);
    }

    public function getCloseDate(): string
    {
        return $this->getProcessedValue($this->closeDate);
    }

    public function getStage(): string
    {
        return $this->getProcessedValue($this->stage);
    }

    public function push(Form $form, Client $client): void
    {
        $this->processLeads($form, $client);
        $this->processAccounts($form, $client);
        $this->processContacts($form, $client);
        $this->processOpportunities($form, $client);
    }

    private function isCreateTasksForDuplicates(): bool
    {
        return $this->convertLeadsToTasks;
    }

    private function processLeads(Form $form, Client $client): void
    {
        if (!$this->mapLeads) {
            $this->logger->debug('No Leads mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->leadMapping, self::CATEGORY_LEAD);
        if (!$mapping) {
            return;
        }

        if ($this->createTasksForDuplicates($form, $client, $mapping)) {
            return;
        }

        $mapping = $this->triggerPushEvent(self::CATEGORY_LEAD, $mapping);

        $leadId = null;
        $leadEmail = $mapping['Email'] ?? null;
        if ($this->checkLeadDuplicates && $leadEmail) {
            $this->logger->debug('Checking for existing Lead', ['email' => $leadEmail]);

            $existingRecord = $this->querySingle(
                $client,
                'SELECT Id FROM Lead WHERE Email = \'%s\' LIMIT 1',
                [$leadEmail],
            );

            $leadId = $existingRecord->Id ?? null;

            if ($leadId) {
                $this->logger->debug('Found existing Lead', ['leadId' => $leadId]);
            } else {
                $this->logger->debug('No existing Lead found');
            }
        }

        $requestConfiguration = [
            'headers' => [
                'Sforce-Auto-Assign' => $this->assignLeadOwner ? 'TRUE' : 'FALSE',
            ],
            'json' => $mapping,
        ];

        if ($leadId) {
            // Remove empty values
            $requestConfiguration['json'] = array_filter($requestConfiguration['json']);

            [$response] = $this->getJsonResponse(
                $client->patch(
                    $this->getEndpoint('/sobjects/Lead/'.$leadId),
                    $requestConfiguration,
                )
            );

            $this->logger->info('Lead updated', ['id' => $leadId]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            [$response, $json] = $this->getJsonResponse(
                $client->post(
                    $this->getEndpoint('/sobjects/Lead'),
                    $requestConfiguration
                )
            );

            $leadId = $json->id;
            $this->logger->info('New Lead created', ['id' => $leadId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if ($this->filesForLeads) {
            $this->linkFilesTo($leadId, $form, $client);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_LEAD, $response);
    }

    private function processAccounts(Form $form, Client $client): void
    {
        if (!$this->mapAccounts) {
            $this->logger->debug('No Accounts mapped, skipping.');

            return;
        }

        $contactMapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);

        $mapping = $this->processMapping($form, $this->accountMapping, self::CATEGORY_ACCOUNT);
        if (!$mapping) {
            return;
        }

        $appendAccountFields = [];
        foreach ($this->accountMapping as $item) {
            if (FieldMapItem::TYPE_RELATION === $item->getType()) {
                $field = $form->get($item->getValue());

                if ($field instanceof CheckboxesField) {
                    $appendAccountFields[] = $item->getSource();
                }
            }
        }

        $accountName = $mapping['Name'] ?? null;
        $accountWebsite = $mapping['Website'] ?? null;
        $contactFirstName = $contactMapping['FirstName'] ?? null;
        $contactLastName = $contactMapping['LastName'] ?? null;
        $contactEmail = $contactMapping['Email'] ?? null;
        $contactName = trim("{$contactFirstName} {$contactLastName}");

        if (empty($accountName)) {
            $accountName = $contactName;
            $mapping['Name'] = $accountName;
            $this->logger->debug('No Account name provided, using Contact name instead', ['accountName' => $accountName]);
        }

        // We'll query
        $appendAccountFieldsQuery = '';

        // Check if contact has an email which we can use to get account website
        if ($this->duplicateCheck && !$accountWebsite && $contactEmail) {
            $accountWebsite = $this->extractDomainFromEmail($contactEmail);

            if ($accountWebsite) {
                $mapping['Website'] = $accountWebsite;
                $this->logger->debug('No Account website provided, using Contact email domain instead', ['accountWebsite' => $accountWebsite]);
            }
        }

        // We'll query Account's contacts so we can later extract a website domain from contact's email address
        if (!$accountWebsite) {
            $appendAccountFieldsQuery = ', (select id, email from Contacts)';
        }

        // We'll query fields to which we have to append new values
        if ($appendAccountFields) {
            $appendAccountFieldsQuery = ', '.implode(', ', $appendAccountFields).' ';
        }

        $accountRecord = null;

        // If the advanced mapping is enabled, and we have an account website which we can use for a search
        if ($this->duplicateCheck) {
            if ($accountWebsite) {
                $this->logger->debug('Checking for existing Account by website', ['website' => $accountWebsite]);

                // We'll search for an account with account website
                $accountRecord = $this->querySingle(
                    $client,
                    'SELECT Id'.$appendAccountFieldsQuery." FROM Account WHERE Website = '%s' ORDER BY CreatedDate desc LIMIT 1",
                    [$accountWebsite],
                );
            }
        } else {
            $this->logger->debug('Checking for existing Account by name', ['name' => $accountName]);

            $accountRecord = $this->querySingle(
                $client,
                'SELECT Id'.$appendAccountFieldsQuery." FROM Account WHERE Name = '%s' ORDER BY CreatedDate desc LIMIT 1",
                [$accountName],
            );
        }

        if ($accountRecord) {
            // We'll prepare appendable values
            if ($this->appendAccountData) {
                $mapping = $this->appendValues($mapping, $accountRecord, $appendAccountFields);
            }

            $mapping = $this->triggerPushEvent(self::CATEGORY_ACCOUNT, $mapping);

            $response = $client->patch(
                $this->getEndpoint('/sobjects/Account/'.$accountRecord->Id),
                ['json' => $mapping],
            );

            $this->accountId = $accountRecord->Id;
            $this->logger->info('Existing Account updated', ['id' => $accountRecord->Id]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            $mapping = $this->triggerPushEvent(self::CATEGORY_ACCOUNT, $mapping);

            $response = $client->post(
                $this->getEndpoint('/sobjects/Account'),
                ['json' => $mapping],
            );

            $json = json_decode((string) $response->getBody());
            $this->accountId = $json->id;
            $this->logger->info('New Account created', ['id' => $this->accountId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if ($this->filesForAccounts && $this->accountId) {
            $this->linkFilesTo($this->accountId, $form, $client);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_ACCOUNT, $response);
    }

    private function processContacts(Form $form, Client $client): void
    {
        if (!$this->mapContacts) {
            $this->logger->debug('No Contacts mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->contactMapping, self::CATEGORY_CONTACT);
        if (!$mapping) {
            return;
        }

        $isAppendContactData = $this->appendContactData;

        $appendContactFields = [];
        foreach ($this->contactMapping as $item) {
            if (FieldMapItem::TYPE_RELATION === $item->getType()) {
                $field = $form->get($item->getValue());

                if ($field instanceof CheckboxesField) {
                    $appendContactFields[] = $item->getSource();
                }
            }
        }

        $contactFirstName = $mapping['FirstName'] ?? null;
        $contactLastName = $mapping['LastName'] ?? null;
        $contactEmail = $mapping['Email'] ?? null;
        $contactName = trim("{$contactFirstName} {$contactLastName}");

        $appendFieldsQuery = '';
        if ($appendContactFields) {
            $appendFieldsQuery = ', '.implode(', ', $appendContactFields).' ';
        }

        $contactRecord = null;
        if (!empty($contactEmail)) {
            $this->logger->debug('Checking for existing Contact by email', ['email' => $contactEmail]);

            $contactRecord = $this->querySingle(
                $client,
                'SELECT Id'.$appendFieldsQuery." FROM Contact WHERE Email = '%s' ORDER BY CreatedDate desc LIMIT 1",
                [$contactEmail],
            );

            if ($contactRecord) {
                $this->logger->debug('Found existing Contact', ['id' => $contactRecord->Id]);
            } else {
                $this->logger->debug('No existing Contact found');
            }
        }

        if (!$contactRecord) {
            $this->logger->debug('Checking for existing Contact by name', ['name' => $contactName]);

            $contactRecord = $this->querySingle(
                $client,
                'SELECT Id'.$appendFieldsQuery." FROM Contact WHERE Name = '%s' ORDER BY CreatedDate desc LIMIT 1",
                [$contactName],
            );

            if ($contactRecord) {
                $this->logger->debug('Found existing Contact', ['id' => $contactRecord->Id]);
            } else {
                $this->logger->debug('No existing Contact found');
            }
        }

        if ($this->accountId) {
            $this->logger->debug('Assigning Account to Contact', ['id' => $this->accountId]);
            $mapping['AccountId'] = $this->accountId;
        }

        if ($contactRecord) {
            $contactId = $contactRecord->Id;

            // We'll prepare appendable values
            if ($isAppendContactData) {
                $mapping = $this->appendValues($mapping, $contactRecord, $appendContactFields);
            }

            $response = $client->patch(
                $this->getEndpoint('/sobjects/Contact/'.$contactRecord->Id),
                ['json' => $mapping],
            );

            $this->logger->info('Existing Contact updated', ['id' => $contactId]);
            $this->logger->debug('With Mapping', $mapping);
        } else {
            [$response, $json] = $this->getJsonResponse(
                $client->post(
                    $this->getEndpoint('/sobjects/Contact'),
                    ['json' => $mapping],
                )
            );

            $contactId = $json->id;
            $this->logger->info('New Contact created', ['id' => $contactId]);
            $this->logger->debug('With Mapping', $mapping);
        }

        if ($this->filesForContacts && $contactId) {
            $this->linkFilesTo($contactId, $form, $client);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_CONTACT, $response);
    }

    private function processOpportunities(Form $form, Client $client): void
    {
        if (!$this->mapOpportunities) {
            $this->logger->debug('No Opportunities mapped, skipping.');

            return;
        }

        $mapping = $this->processMapping($form, $this->opportunityMapping, self::CATEGORY_OPPORTUNITY);
        if (!$mapping) {
            return;
        }

        try {
            $closeDate = new Carbon($this->closeDate);
        } catch (\Exception $e) {
            $closeDate = new Carbon();
        }

        $mapping['CloseDate'] = $closeDate->toIso8601ZuluString();
        $mapping['StageName'] = $this->getStage();
        if ($this->accountId) {
            $this->logger->debug('Assigning Account to Opportunity', ['id' => $this->accountId]);
            $mapping['AccountId'] = $this->accountId;
        }

        [$response, $json] = $this->getJsonResponse(
            $client->post(
                $this->getEndpoint('/sobjects/Opportunity'),
                ['json' => $mapping],
            )
        );

        $this->logger->info('New Opportunity created', ['id' => $json->id]);
        $this->logger->debug('With Mapping', $mapping);

        if ($this->filesForOpportunities) {
            $this->linkFilesTo($json->id, $form, $client);
        }

        $this->triggerAfterResponseEvent(self::CATEGORY_OPPORTUNITY, $response);
    }

    private function createTasksForDuplicates(Form $form, Client $client, array $keyValueList): bool
    {
        // Check for existing clients
        if ($this->isCreateTasksForDuplicates() && isset($keyValueList['Email'])) {
            $email = $keyValueList['Email'];

            $contact = $this->querySingle(
                $client,
                "SELECT Id, Email, OwnerId FROM Contact WHERE Email = '%s' LIMIT 1",
                [$email]
            );

            if ($contact) {
                $description = '';
                foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
                    $description .= "{$field->getLabel()}: {$field->getValueAsString()}\n";
                }

                try {
                    $dueDate = $this->getTaskDueDate() ?: '+2 days';
                    $dueDate = new Carbon($dueDate, 'UTC');
                } catch (\Exception) {
                    $dueDate = new Carbon('+2 days');
                }

                $subject = $this->getTaskSubject() ?: 'New Followup';

                $payload = [
                    'Subject' => $subject,
                    'WhoId' => $contact->Id,
                    'Description' => $description,
                    'ActivityDate' => $dueDate->toDateString(),
                ];

                if ($contact->OwnerId) {
                    $payload['OwnerId'] = $contact->OwnerId;
                }

                [$response, $json] = $this->getJsonResponse($client->post(
                    $this->getEndpoint('/sobjects/Task'),
                    ['json' => $payload],
                ));

                $this->logger->info('Task created for existing Contact', ['contactId' => $contact->Id, 'taskId' => $json->id]);
                $this->logger->debug('With Mapping', $payload);

                return 201 === $response->getStatusCode();
            }
        }

        $this->logger->debug('Checked for existing contacts, none found.');

        return false;
    }

    private function extractDomainFromEmail(string $email): ?string
    {
        if (preg_match('/^.*@([^@]+)$$/', $email, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Goes through all of the mapped values, checks which values have to be appended and appends them to the record's
     * values.
     */
    private function appendValues(array $mappedValues, mixed $record, array $appendFields): array
    {
        foreach ($mappedValues as $fieldHandle => $value) {
            if (\in_array($fieldHandle, $appendFields)) {
                if (isset($record->{$fieldHandle}) && $record->{$fieldHandle}) {
                    if ($value) {
                        $mappedValues[$fieldHandle] = $record->{$fieldHandle}.';'.$value;
                    } else {
                        $mappedValues[$fieldHandle] = $record->{$fieldHandle};
                    }

                    // Clean up duplicate values
                    $valueArray = explode(';', $mappedValues[$fieldHandle]);
                    $valueArray = array_unique($valueArray);
                    $mappedValues[$fieldHandle] = implode(';', $valueArray);
                }
            }
        }

        return $mappedValues;
    }

    private function getLinkedContentDocumentIds(Form $form, Client $client): array
    {
        if (null !== $this->linkedContentDocumentIds) {
            return $this->linkedContentDocumentIds;
        }

        $this->linkedContentDocumentIds = [];

        $fields = $form->getLayout()->getFields(FileUploadInterface::class);
        if (!$fields->count()) {
            return $this->linkedContentDocumentIds;
        }

        $this->linkedContentDocumentIds = [];

        $fileBatch = [];

        /** @var FileUploadInterface $field */
        foreach ($fields as $field) {
            $assets = $field->getAssets();

            /** @var Asset $asset */
            foreach ($assets as $asset) {
                $fileBatch[] = [
                    'method' => 'POST',
                    'url' => '/services/data/'.self::API_VERSION.'/sobjects/ContentVersion',
                    'referenceId' => 'File_'.$asset->id,
                    'body' => [
                        'Title' => $asset->title,
                        'PathOnClient' => $asset->getFilename(),
                        'VersionData' => base64_encode($asset->getContents()),
                    ],
                ];
            }
        }

        if (!$fileBatch) {
            return $this->linkedContentDocumentIds;
        }

        [, $versionData] = $this->getJsonResponse(
            $client->post(
                $this->getEndpoint('/composite'),
                [
                    'json' => [
                        'allOrNone' => true,
                        'compositeRequest' => $fileBatch,
                    ],
                ]
            )
        );

        $versionIds = array_map(
            fn ($item) => $item->body->id,
            $versionData->compositeResponse,
        );

        $linkedDocumentData = $this->query(
            $client,
            "SELECT ContentDocumentId FROM ContentVersion WHERE Id IN ('%s')",
            [implode("', '", $versionIds)],
            false,
        );

        $this->linkedContentDocumentIds = array_map(
            fn ($item) => $item->ContentDocumentId,
            $linkedDocumentData,
        );

        return $this->linkedContentDocumentIds;
    }

    private function linkFilesTo(string $id, Form $form, Client $client): void
    {
        $documentIds = $this->getLinkedContentDocumentIds($form, $client);
        if (!$documentIds) {
            $this->logger->debug('No files to link');

            return;
        }

        $this->logger->debug('Linking files to record', ['id' => $id, 'documentIds' => $documentIds]);

        $composite = array_map(
            fn ($item, $index) => [
                'method' => 'POST',
                'url' => '/services/data/'.self::API_VERSION.'/sobjects/ContentDocumentLink',
                'referenceId' => 'Link'.$index,
                'body' => [
                    'ContentDocumentId' => $item,
                    'LinkedEntityId' => $id,
                    'ShareType' => 'V',
                    'Visibility' => 'AllUsers',
                ],
            ],
            $documentIds,
            array_keys($documentIds),
        );

        $client->post(
            $this->getEndpoint('/composite'),
            [
                'json' => [
                    'allOrNone' => true,
                    'compositeRequest' => $composite,
                ],
            ]
        );

        $this->logger->debug('Files linked to record', ['id' => $id, 'documentIds' => $documentIds]);
    }
}
