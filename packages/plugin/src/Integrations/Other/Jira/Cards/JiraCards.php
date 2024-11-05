<?php

namespace Solspace\Freeform\Integrations\Other\Jira\Cards;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\Jira\BaseJiraIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;

#[Type(
    name: 'Jira',
    type: Type::TYPE_OTHER,
    version: 'v3',
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class JiraCards extends BaseJiraIntegration
{
    public const TYPE_JDOC = 'jdoc';
    public const TYPE_NAME_MAP = 'name-map';

    private const CATEGORY_CARD = 'card';

    #[Flag(self::FLAG_GLOBAL_PROPERTY)]
    #[Input\Text(
        label: 'Project Key',
        instructions: 'Enter the project key for the Jira project you want to interact with. If left empty, it will be auto-populated with the first available project key of your account.',
        order: 2,
    )]
    protected string $projectKey = '';

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[ValueTransformer(FieldMappingTransformer::class)]
    #[VisibilityFilter('Boolean(enabled)')]
    #[Input\Special\Properties\FieldMapping(
        instructions: 'Select the Freeform fields to be mapped to the applicable Jira Issue fields.',
        order: 15,
        source: 'api/integrations/crm/fields/'.self::CATEGORY_CARD,
        parameterFields: ['id' => 'id', 'projectKey' => 'projectKey'],
    )]
    protected ?FieldMapping $mapping = null;

    public function getProjectKey(): string
    {
        return $this->getProcessedValue($this->projectKey);
    }

    public function setProjectKey(string $projectKey): self
    {
        $this->projectKey = $projectKey;

        return $this;
    }

    public function push(Form $form, Client $client): void
    {
        $client->post(
            $this->getEndpoint('issue'),
            [
                'json' => [
                    'fields' => [
                        'project' => [
                            'key' => $this->getProjectKey(),
                        ],
                        'summary' => 'Test issue',
                        'description' => [
                            'type' => 'doc',
                            'version' => 1,
                            'content' => [
                                [
                                    'type' => 'paragraph',
                                    'content' => [
                                        [
                                            'text' => 'This is a test issue',
                                            'type' => 'text',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'issuetype' => [
                            'id' => '10120',
                        ],
                    ],
                ],
            ]
        );
    }

    public function fetchFields(string $category, Client $client): array
    {
        $fields = [];

        $fields[] = new FieldObject(
            'issuetype',
            'Issue Type',
            self::TYPE_NAME_MAP,
            $category,
            true,
            $this->getIssueTypeOptions($client)
        );

        $fields[] = new FieldObject(
            'summary',
            'Summary',
            FieldObject::TYPE_STqRING,
            $category,
            true
        );

        $fields[] = new FieldObject(
            'description',
            'Description',
            self::TYPE_JDOC,
            $category,
            true
        );

        return $fields;
    }

    public function populateParameters(Client $client): void
    {
        if ($this->getProjectKey()) {
            return;
        }

        $response = $client->get($this->getEndpoint('issue/createmeta'));
        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->projects[0])) {
            $this->setProjectKey($json->projects[0]->key);
        }
    }

    protected function getProcessableFields(string $category): array
    {
        return [];
    }

    private function getIssueTypeOptions(Client $client): array
    {
        [, $json] = $this->getJsonResponse(
            $client->get($this->getEndpoint('issue/createmeta/'.$this->getProjectKey().'/issuetypes'))
        );

        $types = [];
        foreach ($json->issueTypes as $issueType) {
            $types[] = [
                'key' => $issueType->id,
                'label' => $issueType->name,
                'description' => $issueType->description,
            ];
        }

        return $types;
    }
}
