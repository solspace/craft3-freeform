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

use GuzzleHttp\Exception\RequestException;
use Solspace\Freeform\Integrations\CRM\Pipedrive\AbstractPipedriveIntegration;
use Solspace\Freeform\Library\Integrations\SettingBlueprint;

class PipedriveDeals extends AbstractPipedriveIntegration
{
    public const TITLE = 'Pipedrive Deals';
    public const LOG_CATEGORY = 'Pipedrive Deals';

    /**
     * Returns a list of additional settings for this integration
     * Could be used for anything, like - AccessTokens.
     *
     * @return SettingBlueprint[]
     */
    public static function getSettingBlueprints(): array
    {
        $settings = parent::getSettingBlueprints();
        $settings[] = new SettingBlueprint(
            SettingBlueprint::TYPE_TEXT,
            self::SETTING_STAGE_ID,
            'Stage ID',
            'Enter the Pipedrive Stage ID you want the deal to be placed in.',
            false
        );

        return $settings;
    }

    /**
     * Push objects to the CRM.
     *
     * @param null $formFields
     */
    public function pushObject(array $keyValueList, $formFields = null): bool
    {
        $client = $this->getAuthorizedClient();

        $orgId = $this->pushOrg($keyValueList);
        $personId = $this->pushPerson($keyValueList, $orgId);

        $fields = $this->getFieldsByCategory('deal', $keyValueList);

        $dealId = null;

        try {
            if ($this->getUserId()) {
                $fields['user_id'] = $this->getUserId();
            }

            if ($personId) {
                $fields['person_id'] = $personId;
            }

            if ($orgId) {
                $fields['org_id'] = $orgId;
            }

            $stageId = $this->getSetting(self::SETTING_STAGE_ID);
            if ($stageId) {
                $fields['stage_id'] = (int) $stageId;
            }

            $response = $client->post(
                $this->getEndpoint('/deals'),
                ['json' => $fields]
            );

            $json = json_decode((string) $response->getBody(), false);
            $dealId = $json->data->id;

            $this->getHandler()->onAfterResponse($this, $response);

            $this->addNote('deal', $dealId, $keyValueList['note___deal'] ?? null);
            $this->addNote('org', $orgId, $keyValueList['note___org'] ?? null);
            $this->addNote('person', $personId, $keyValueList['note___prsn'] ?? null);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();

            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
        }

        return (bool) $dealId;
    }
}
