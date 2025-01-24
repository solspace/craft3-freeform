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

class PipedriveLeads extends AbstractPipedriveIntegration
{
    public const TITLE = 'Pipedrive Leads';
    public const LOG_CATEGORY = 'Pipedrive Leads';

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

        $leadFields = $this->getFieldsByCategory('lead', $keyValueList);

        $leadId = null;

        try {
            if ($orgId) {
                $leadFields['organization_id'] = $orgId;
            }

            if ($personId) {
                $leadFields['person_id'] = $personId;
            }

            if ($this->getUserId()) {
                $leadFields['owner_id'] = $this->getUserId();
            }

            $value = new \stdClass();
            $value->amount = $leadFields['value'] ?? 0;
            $value->currency = $leadFields['currency'] ?? 'USD';

            unset($leadFields['currency']);
            $leadFields['value'] = $value->amount ? $value : null;

            $note = $leadFields['note'];
            unset($leadFields['note']);

            $response = $client->post(
                $this->getEndpoint('/leads'),
                ['json' => $leadFields]
            );

            $json = json_decode((string) $response->getBody(), false);
            $leadId = $json->data->id;

            $this->getHandler()->onAfterResponse($this, $response);

            $this->addNote('lead', $leadId, $note ?? null);
            $this->addNote('org', $orgId, $keyValueList['note___org'] ?? null);
            $this->addNote('person', $personId, $keyValueList['note___prsn'] ?? null);
        } catch (RequestException $e) {
            $responseBody = (string) $e->getResponse()->getBody();

            $this->getLogger()->error($responseBody, ['exception' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());
        }

        return (bool) $leadId;
    }
}
