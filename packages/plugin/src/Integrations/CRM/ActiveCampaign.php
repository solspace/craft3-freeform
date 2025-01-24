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

namespace Solspace\Freeform\Integrations\CRM;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\IntegrationStorageInterface;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class ActiveCampaign extends AbstractCRMIntegration
{
    public const SETTING_API_TOKEN = 'api_token';
    public const SETTING_API_URL = 'api_url';
    public const SETTING_PIPELINE = 'pipeline';
    public const SETTING_PIPELINE_ID = 'pipeline_id';
    public const SETTING_STAGE = 'stage';
    public const SETTING_STAGE_ID = 'stage_id';
    public const SETTING_OWNER = 'owner';
    public const SETTING_OWNER_ID = 'owner_id';

    public const TITLE = 'Active Campaign';
    public const LOG_CATEGORY = 'Active Campaign';

    /**
     * Returns a list of additional settings for this integration
     * Could be used for anything, like - AccessTokens.
     *
     * @return SettingBlueprint[]
     */
    public static function getSettingBlueprints(): array
    {
        return [
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_API_TOKEN,
                'API Token',
                'Enter your API Token here.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_API_URL,
                'API URL',
                'Enter your API specific URL here.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_PIPELINE,
                'Pipeline',
                'Enter the name or ID of the desired Pipeline.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_STAGE,
                'Stage',
                'Enter the name or ID of the desired Stage.',
                true
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_TEXT,
                self::SETTING_OWNER,
                'Owner (Optional if auto-assign enabled in AC)',
                'Enter the username or ID of the user you wish to assign as the deal owner. If you don\'t specify an owner, Active Campaign will auto-assign an owner if it is set up.',
                false
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_PIPELINE_ID,
                null,
                null
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_STAGE_ID,
                null,
                null
            ),
            new SettingBlueprint(
                SettingBlueprint::TYPE_INTERNAL,
                self::SETTING_OWNER_ID,
                null,
                null
            ),
        ];
    }

    /**
     * Push objects to the CRM.
     *
     * @param null|mixed $formFields
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $client = $this->generateAuthorizedClient();

        $deal = $contact = $org = [];
        $dealProps = $contactProps = [];

        foreach ($keyValueList as $key => $value) {
            preg_match('/^(\w+)___(.+)$/', $key, $matches);

            [$all, $target, $propName] = $matches;

            if (is_numeric($propName)) {
                switch ($target) {
                    case 'contact':
                        if (\is_array($value)) {
                            $value = '||'.implode('||', $value).'||';
                        }

                        $contactProps[] = ['contact' => null, 'field' => (int) $propName, 'value' => $value];

                        break;

                    case 'deal':
                        $dealProps[] = ['dealId' => null, 'customFieldId' => (int) $propName, 'fieldValue' => $value];

                        break;
                }
            } else {
                switch ($target) {
                    case 'contact':
                        $contact[$propName] = $value;

                        break;

                    case 'organisation':
                        $org[$propName] = $value;

                        break;

                    case 'deal':
                        $deal[$propName] = $value;

                        break;
                }
            }
        }

        $organizationId = null;
        if ($org) {
            try {
                $response = $client->post(
                    $this->getEndpoint('/organizations'),
                    ['json' => ['organization' => $org]]
                );

                $json = json_decode((string) $response->getBody(), false);
                if (isset($json->organization)) {
                    $organizationId = $json->organization->id;
                }

                $this->getHandler()->onAfterResponse($this, $response);
            } catch (RequestException $e) {
                if (422 === $e->getCode()) {
                    try {
                        $response = $client->get($this->getEndpoint('/organizations'));
                        $list = json_decode($response->getBody(), false);
                        foreach ($list->organizations as $organization) {
                            if (strtolower($organization->name) === strtolower($org['name'])) {
                                $organizationId = $organization->id;

                                break;
                            }
                        }
                    } catch (RequestException $exception) {
                        $responseBody = (string) $e->getResponse()->getBody();

                        $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
                    }
                } else {
                    $responseBody = (string) $e->getResponse()->getBody();

                    $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
                }
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

        $contactId = null;
        if (!empty($contact)) {
            if ($organizationId) {
                $contact['orgid'] = $organizationId;
            }

            $mailingListId = null;
            if (isset($contact['mailing_list_id'])) {
                $mailingListId = $contact['mailing_list_id'];
                unset($contact['mailing_list_id']);
            }

            try {
                $response = $client->post(
                    $this->getEndpoint('/contact/sync'),
                    ['json' => ['contact' => $contact]]
                );

                $json = json_decode($response->getBody(), false);
                if (isset($json->contact)) {
                    $contactId = $json->contact->id;
                }

                $this->getHandler()->onAfterResponse($this, $response);

                foreach ($contactProps as $prop) {
                    $prop['contact'] = $contactId;

                    $client->post(
                        $this->getEndpoint('/fieldValues'),
                        ['json' => ['fieldValue' => $prop]]
                    );
                }
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }

            if ($contactId && $mailingListId) {
                try {
                    $client->post(
                        $this->getEndpoint('/contactLists'),
                        [
                            'json' => [
                                'contactList' => [
                                    'list' => $mailingListId,
                                    'contact' => $contactId,
                                    'status' => 1,
                                ],
                            ],
                        ]
                    );
                } catch (RequestException $e) {
                    $this->getLogger()->error($e->getRequest()->getBody());
                }
            }
        }

        if (!empty($deal)) {
            $pipelineId = $this->fetchPipelineId($deal['group'] ?? $this->getPipelineId());
            if ($pipelineId) {
                $deal['group'] = $pipelineId;
            }

            $stageId = $this->fetchStageId($deal['stage'] ?? $this->getStageId(), $pipelineId);
            if ($stageId) {
                $deal['stage'] = $stageId;
            }

            $ownerId = $this->fetchOwnerId($deal['owner'] ?? $this->getOwnerId());
            if ($ownerId) {
                $deal['owner'] = $ownerId;
            }

            if ($contactId) {
                $deal['contact'] = $contactId;
            }

            try {
                $response = $client->post(
                    $this->getEndpoint('/deals'),
                    ['json' => ['deal' => $deal]]
                );

                $json = json_decode((string) $response->getBody(), false);
                if (isset($json->deal)) {
                    $dealId = $json->deal->id;

                    foreach ($dealProps as $prop) {
                        $prop['dealId'] = $dealId;

                        $client->post(
                            $this->getEndpoint('/dealCustomFieldData'),
                            ['json' => ['dealCustomFieldDatum' => $prop]]
                        );
                    }
                }

                $this->getHandler()->onAfterResponse($this, $response);
            } catch (RequestException $e) {
                $responseBody = (string) $e->getResponse()->getBody();

                $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
            } catch (\Exception $e) {
                $this->getLogger()->error($e->getMessage());
            }
        }

        return true;
    }

    /**
     * Check if it's possible to connect to the API.
     *
     * @throws IntegrationException
     */
    public function checkConnection(): bool
    {
        try {
            $response = $this->generateAuthorizedClient()->get($this->getEndpoint('/'));

            return 200 === $response->getStatusCode();
        } catch (\Exception $e) {
            throw new IntegrationException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Fetch the custom fields from the integration.
     *
     * @return FieldObject[]
     */
    public function fetchFields(): array
    {
        $client = $this->generateAuthorizedClient();

        $contactFields = [
            new FieldObject('contact___mailing_list_id', 'Mailing List ID (Contact)', FieldObject::TYPE_NUMERIC),
            new FieldObject('contact___email', 'Email (Contact)', FieldObject::TYPE_STRING),
            new FieldObject('contact___firstName', 'First Name (Contact)', FieldObject::TYPE_STRING),
            new FieldObject('contact___lastName', 'Last Name (Contact)', FieldObject::TYPE_STRING),
            new FieldObject('contact___phone', 'Phone (Contact)', FieldObject::TYPE_STRING),
        ];

        $dealFields = [
            new FieldObject('deal___title', 'Title (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___description', 'Description (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___value', 'Value (Deal)', FieldObject::TYPE_NUMERIC),
            new FieldObject('deal___currency', 'Currency (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___group', 'Group (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___owner', 'Owner (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___percent', 'Percent (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___stage', 'Stage (Deal)', FieldObject::TYPE_STRING),
            new FieldObject('deal___status', 'Status (Deal)', FieldObject::TYPE_NUMERIC),
        ];

        $orgFields = [
            new FieldObject('organisation___name', 'Name (Organisation)', FieldObject::TYPE_STRING),
        ];

        try {
            $response = $client->get($this->getEndpoint('/fields'));
            $data = json_decode($response->getBody(), false);

            foreach ($data->fields as $field) {
                $type = FieldObject::TYPE_STRING;

                switch ($field->type) {
                    case 'dropdown':
                    case 'multiselect':
                    case 'checkbox':
                        $type = FieldObject::TYPE_ARRAY;

                        break;

                    case 'date':
                        $type = FieldObject::TYPE_DATETIME;

                        break;

                    case 'currency':
                        continue 2;
                }

                $contactFields[] = new FieldObject(
                    'contact___'.$field->id,
                    $field->title.' (Contact)',
                    $type,
                    (bool) $field->isrequired
                );
            }
        } catch (RequestException $e) {
            $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);
        }

        try {
            $response = $client->get($this->getEndpoint('/dealCustomFieldMeta'));
            $data = json_decode($response->getBody(), false);

            foreach ($data->dealCustomFieldMeta as $field) {
                $type = FieldObject::TYPE_STRING;

                switch ($field->fieldType) {
                    case 'dropdown':
                    case 'multiselect':
                    case 'checkbox':
                        $type = FieldObject::TYPE_ARRAY;

                        break;

                    case 'date':
                        $type = FieldObject::TYPE_DATETIME;

                        break;

                    case 'currency':
                        continue 2;
                }

                $contactFields[] = new FieldObject(
                    'deal___'.$field->id,
                    $field->fieldLabel.' (Deal)',
                    $type,
                    (bool) $field->isRequired
                );
            }
        } catch (RequestException $e) {
            $this->getLogger()->error($e->getMessage(), ['response' => $e->getResponse()]);
        }

        return array_merge($contactFields, $dealFields, $orgFields);
    }

    /**
     * A method that initiates the authentication.
     */
    public function initiateAuthentication()
    {
    }

    /**
     * Perform anything necessary before this integration is saved.
     *
     * @throws IntegrationException
     */
    public function onBeforeSave(IntegrationStorageInterface $model)
    {
        $apiToken = $this->getApiToken();
        $apiUrl = $this->getApiRootUrl();
        $pipeline = $this->getPipeline();
        $stage = $this->getStage();
        $owner = $this->getOwner();

        // If one of these isn't present, we just return void
        if (!$apiToken || !$apiUrl) {
            return;
        }

        $pipelineId = $this->fetchPipelineId($pipeline);
        if (!$pipelineId) {
            $pipeline = '';
        }

        $stageId = $this->fetchStageId($stage, $pipelineId);
        if (!$stageId) {
            $stage = '';
        }

        $ownerId = $this->fetchOwnerId($owner);
        if (!$ownerId) {
            $owner = '';
        }

        $this->setSetting(self::SETTING_PIPELINE, $pipeline);
        $this->setSetting(self::SETTING_PIPELINE_ID, $pipelineId);
        $this->setSetting(self::SETTING_STAGE, $stage);
        $this->setSetting(self::SETTING_STAGE_ID, $stageId);
        $this->setSetting(self::SETTING_OWNER, $owner);
        $this->setSetting(self::SETTING_OWNER_ID, $ownerId);

        $model->updateSettings($this->getSettings());
    }

    /**
     * Authorizes the application
     * Returns the access_token.
     */
    public function fetchAccessToken(): string
    {
        return $this->getApiToken();
    }

    /**
     * Get the base ActiveCampaign API URL.
     */
    protected function getApiRootUrl(): string
    {
        return rtrim($this->getSetting(self::SETTING_API_URL), '/').'/api/3/';
    }

    /**
     * Gets the API Token for ActiveCampaign from settings config.
     *
     * @return null|mixed
     *
     * @throws IntegrationException
     */
    private function getApiToken()
    {
        return $this->getSetting(self::SETTING_API_TOKEN);
    }

    /**
     * @return null|string
     *
     * @throws IntegrationException
     */
    private function getPipeline()
    {
        return $this->getSetting(self::SETTING_PIPELINE);
    }

    /**
     * @return null|int
     *
     * @throws IntegrationException
     */
    private function getPipelineId()
    {
        return $this->getSetting(self::SETTING_PIPELINE_ID);
    }

    /**
     * @return null|string
     *
     * @throws IntegrationException
     */
    private function getStage()
    {
        return $this->getSetting(self::SETTING_STAGE);
    }

    /**
     * @return null|int
     *
     * @throws IntegrationException
     */
    private function getStageId()
    {
        return $this->getSetting(self::SETTING_STAGE_ID);
    }

    /**
     * @return null|string
     *
     * @throws IntegrationException
     */
    private function getOwner()
    {
        return $this->getSetting(self::SETTING_OWNER);
    }

    /**
     * @return null|int
     *
     * @throws IntegrationException
     */
    private function getOwnerId()
    {
        return $this->getSetting(self::SETTING_OWNER_ID);
    }

    private function generateAuthorizedClient(): Client
    {
        return new Client([
            'headers' => [
                'Api-Token' => $this->getApiToken(),
            ],
        ]);
    }

    private function fetchPipelineId($pipeline = null)
    {
        if (!$pipeline) {
            return null;
        }

        if (is_numeric($pipeline)) {
            return $pipeline;
        }

        try {
            $client = $this->generateAuthorizedClient();
            $response = $client->get(
                $this->getEndpoint('/dealGroups'),
                ['query' => ['filters[title]' => $pipeline]]
            );

            $json = json_decode($response->getBody(), false);
            if (isset($json->dealGroups) && \count($json->dealGroups)) {
                $item = $json->dealGroups[0];

                return $item->id;
            }
        } catch (RequestException $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        return null;
    }

    private function fetchStageId($stage = null, $pipeline = null)
    {
        if (!$stage) {
            return null;
        }

        $pipelineId = $this->fetchPipelineId($pipeline);

        if (is_numeric($stage)) {
            return $stage;
        }

        try {
            $client = $this->generateAuthorizedClient();

            $query = ['filters[title]' => $stage];
            if ($pipelineId) {
                $query['filters[d_groupid]'] = $pipelineId;
            }
            $response = $client->get(
                $this->getEndpoint('/dealStages'),
                ['query' => $query]
            );

            $json = json_decode($response->getBody(), false);
            if (isset($json->dealStages) && \count($json->dealStages)) {
                $item = $json->dealStages[0];

                return $item->id;
            }
        } catch (RequestException $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        return null;
    }

    private function fetchOwnerId($owner = null)
    {
        if (!$owner) {
            return null;
        }

        if (is_numeric($owner)) {
            return $owner;
        }

        try {
            $client = $this->generateAuthorizedClient();
            $response = $client->get($this->getEndpoint('/users/username/'.$owner));

            $json = json_decode($response->getBody(), false);
            if (isset($json->user)) {
                return $json->user->id;
            }
        } catch (RequestException $e) {
            $this->getLogger()->warning($e->getMessage());
        }

        return null;
    }
}
