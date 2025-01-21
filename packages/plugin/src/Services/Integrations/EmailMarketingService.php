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

namespace Solspace\Freeform\Services\Integrations;

use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegrationInterface;
use Solspace\Freeform\Records\EmailMarketingFieldRecord;
use Solspace\Freeform\Records\EmailMarketingListRecord;

class EmailMarketingService extends IntegrationsService
{
    public function getListObjectById(?int $id): ?ListObject
    {
        $record = EmailMarketingListRecord::findOne(['id' => $id]);
        if (!$record) {
            return null;
        }

        return new ListObject(
            $record->resourceId,
            $record->name,
            $record->memberCount,
            $record->id,
        );
    }

    /**
     * @return ListObject[]
     */
    public function getLists(
        EmailMarketingIntegrationInterface $integration,
        bool $refresh = false
    ): array {
        $existingRecords = EmailMarketingListRecord::find()
            ->where(['integrationId' => $integration->getId()])
            ->indexBy('resourceId')
            ->all()
        ;

        $client = $this->clientProvider->getAuthorizedClient($integration);

        if ($refresh || empty($existingRecords)) {
            $logger = $this->loggerProvider->getLogger($integration);

            $lists = $integration->fetchLists($client);
            $logger->debug(\sprintf('Fetched %d Lists', \count($lists)));

            $newRecords = [];

            $usedIds = [];
            $newLists = [];
            foreach ($lists as $list) {
                if (!\array_key_exists($list->getResourceId(), $existingRecords)) {
                    $newLists[] = $list;
                }

                $usedIds[] = $list->getResourceId();
            }

            foreach ($newLists as $list) {
                $record = new EmailMarketingListRecord();
                $record->integrationId = $integration->getId();
                $record->resourceId = $list->getResourceId();
                $record->name = $list->getName();
                $record->memberCount = $list->getMemberCount();
                $record->save();

                $newRecords[$list->getResourceId()] = $record;
            }

            foreach ($existingRecords as $handle => $record) {
                if (!\in_array($handle, $usedIds)) {
                    $record->delete();
                } else {
                    $newRecords[$handle] = $record;
                }
            }

            $existingRecords = $newRecords;
        }

        return array_map(
            fn (EmailMarketingListRecord $record) => new ListObject(
                $record->resourceId,
                $record->name,
                $record->memberCount,
                $record->id,
            ),
            $existingRecords
        );
    }

    /**
     * @return FieldObject[]
     */
    public function getFields(
        ListObject $list,
        EmailMarketingIntegrationInterface $integration,
        string $category,
        bool $refresh = false
    ): array {
        $existingRecords = EmailMarketingFieldRecord::find()
            ->where([
                'mailingListId' => $list->getId(),
                'category' => $category,
            ])
            ->indexBy('handle')
            ->all()
        ;

        if ($refresh || empty($existingRecords)) {
            $client = $this->clientProvider->getAuthorizedClient($integration);
            $logger = $this->loggerProvider->getLogger($integration);

            $fields = $integration->fetchFields($list, $category, $client);
            $logger->debug(\sprintf('Fetched %d Fields', \count($fields)), ['category' => $category]);

            $usedHandles = [];
            $newFields = [];
            foreach ($fields as $field) {
                if (!\array_key_exists($field->getHandle(), $existingRecords)) {
                    $newFields[] = $field;
                }

                $usedHandles[] = $field->getHandle();
            }

            foreach ($newFields as $field) {
                $record = new EmailMarketingFieldRecord();
                $record->mailingListId = $list->getId();
                $record->handle = $field->getHandle();
                $record->label = $field->getLabel();
                $record->type = $field->getType();
                $record->required = $field->isRequired();
                $record->options = json_encode($field->getOptions()->getIterator()->getArrayCopy());
                $record->category = $category;
                $record->save();

                $existingRecords[$field->getHandle()] = $record;
            }

            foreach ($existingRecords as $handle => $record) {
                if (!\in_array($handle, $usedHandles)) {
                    $record->delete();
                    unset($existingRecords[$handle]);
                }
            }
        }

        return array_map(
            fn (EmailMarketingFieldRecord $record) => new FieldObject(
                $record->handle,
                $record->label,
                $record->type,
                $record->category,
                $record->required,
                JsonHelper::decode($record->options, true),
            ),
            $existingRecords
        );
    }
}
