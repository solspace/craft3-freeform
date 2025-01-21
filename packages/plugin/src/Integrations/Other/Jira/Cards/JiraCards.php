<?php

namespace Solspace\Freeform\Integrations\Other\Jira\Cards;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\FieldMapping\FieldMapping;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Input\Special\Properties\FieldMappingTransformer;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Integrations\Other\Jira\BaseJiraIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObjectOption;

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
    public const TYPE_ID_MAP = 'id-map';
    public const TYPE_KEY_MAP = 'key-map';

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
        $mapping = $this->processMapping($form, $this->mapping, self::CATEGORY_CARD);
        if (!$mapping) {
            return;
        }

        $mapping['project'] = ['key' => $this->getProjectKey()];

        [$response, $json] = $this->getJsonResponse($client->post(
            $this->getEndpoint('issue'),
            ['json' => ['fields' => $mapping]]
        ));

        $this->logger->info('Jira card created', ['id' => $json->id, 'key' => $json->key]);
        $this->logger->debug('With Mapping', $mapping);

        $this->triggerAfterResponseEvent(self::CATEGORY_CARD, $response);
    }

    public function fetchFields(string $category, Client $client): array
    {
        $query = http_build_query([
            'projectKeys' => $this->getProjectKey(),
            'expand' => 'projects.issuetypes.fields',
        ]);

        [, $json] = $this->getJsonResponse(
            $client->get(
                $this->getEndpoint('issue/createmeta'),
                ['query' => $query]
            )
        );

        $userOptions = $this->getUserOptions($client);

        $issueTypeOptions = [];
        if (isset($json->projects[0])) {
            $project = $json->projects[0];

            foreach ($project->issuetypes as $issueType) {
                $issueTypeOptions[] = [
                    'key' => $issueType->name,
                    'label' => $issueType->name,
                    'description' => $issueType->description,
                ];
            }
        }

        $fields = [
            'issuetype' => new FieldObject(
                'issuetype',
                'Issue Type',
                self::TYPE_NAME_MAP,
                $category,
                true,
                $issueTypeOptions,
            ),
            'parent' => new FieldObject(
                'parent',
                'Parent Key',
                self::TYPE_KEY_MAP,
                $category,
                false,
            ),
            'summary' => new FieldObject(
                'summary',
                'Summary',
                FieldObject::TYPE_STRING,
                $category,
                true,
            ),
            'description' => new FieldObject(
                'description',
                'Description',
                self::TYPE_JDOC,
                $category,
                false,
            ),
            'reporter' => new FieldObject(
                'reporter',
                'Reporter',
                self::TYPE_ID_MAP,
                $category,
                false,
                $userOptions,
            ),
            'assignee' => new FieldObject(
                'assignee',
                'Assignee',
                self::TYPE_ID_MAP,
                $category,
                false,
                $userOptions,
            ),
            'priority' => new FieldObject(
                'priority',
                'Priority',
                self::TYPE_ID_MAP,
                $category,
                false,
                $this->getPriorityOptions($client),
            ),
            'labels' => new FieldObject(
                'labels',
                'Labels',
                FieldObject::TYPE_ARRAY,
                $category,
                false,
            ),
            'components' => new FieldObject(
                'components',
                'Components',
                FieldObject::TYPE_ARRAY,
                $category,
                false,
                $this->getComponentOptions($client),
            ),
            'versions' => new FieldObject(
                'versions',
                'Versions',
                FieldObject::TYPE_ARRAY,
                $category,
                false,
                $this->getVersionOptions($client),
            ),
            'duedate' => new FieldObject(
                'duedate',
                'Due Date',
                FieldObject::TYPE_DATE,
                $category,
                false,
            ),
        ];

        return array_values($fields);
    }

    public function populateParameters(Client $client): void
    {
        if ($this->getProjectKey()) {
            $this->logger->debug('Project key already set, skipping auto-population');

            return;
        }

        $response = $client->get($this->getEndpoint('issue/createmeta'));
        $json = json_decode((string) $response->getBody(), false);

        if (isset($json->projects[0])) {
            $this->setProjectKey($json->projects[0]->key);
            $this->logger->debug('Project key auto-populated', ['projectKey' => $this->getProjectKey()]);
        }
    }

    protected function getProcessableFields(string $category): array
    {
        return Freeform::getInstance()->crm->getFields($this, $category);
    }

    private function getUserOptions(Client $client): array
    {
        [, $json] = $this->getJsonResponse(
            $client->get(
                $this->getEndpoint('user/assignable/multiProjectSearch'),
                ['query' => ['projectKeys' => $this->getProjectKey()]]
            )
        );

        $logOptions = array_map(
            fn ($user) => ['accountId' => $user->accountId, 'displayName' => $user->displayName],
            $json
        );

        $this->logger->debug('Users fetched', $logOptions);

        return array_map(
            fn ($user) => new FieldObjectOption(
                $user->accountId,
                $user->displayName,
                'Account ID: '.$user->accountId,
            ),
            $json
        );
    }

    private function getComponentOptions(Client $client): array
    {
        $components = $this->getPaginatedResults(
            new Request(
                'GET',
                $this->getEndpoint('component?projectIdsOrKeys='.$this->getProjectKey())
            ),
            $client
        );

        $this->logger->debug('Components fetched', $components);

        return array_map(
            fn ($component) => new FieldObjectOption(
                $component->id,
                $component->name,
            ),
            $components
        );
    }

    private function getPriorityOptions(Client $client): array
    {
        [, $json] = $this->getJsonResponse($client->get($this->getEndpoint('priority')));

        $this->logger->debug('Priorities fetched', $json);

        return array_map(
            fn ($priority) => new FieldObjectOption(
                $priority->id,
                $priority->name,
            ),
            $json
        );
    }

    private function getVersionOptions(Client $client): array
    {
        $versions = $this->getPaginatedResults(
            new Request(
                'GET',
                $this->getEndpoint('/project/'.$this->getProjectKey().'/version')
            ),
            $client
        );

        $this->logger->debug('Versions fetched', $versions);

        return array_map(
            fn ($priority) => new FieldObjectOption(
                $priority->id,
                $priority->name,
                $priority->description,
            ),
            $versions
        );
    }

    private function getPaginatedResults(Request $request, Client $client): array
    {
        $results = [];
        $startAt = 0;
        $maxResults = 50;

        $baseUrl = $request->getUri();

        do {
            $uri = new Uri($baseUrl);
            $query = $uri->getQuery();
            $queryParts = explode('&', $query);
            $queryParts[] = 'startAt='.$startAt;
            $queryParts[] = 'maxResults='.$maxResults;

            $response = $client->send(
                $request->withUri($uri->withQuery(implode('&', $queryParts)))
            );

            $json = json_decode((string) $response->getBody(), false);
            $results = array_merge($results, $json->values);

            $startAt += $maxResults;
        } while ($startAt < $json->total);

        return $results;
    }
}
