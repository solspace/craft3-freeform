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
use Solspace\Freeform\Library\Integrations\Types\CRM\CRMIntegrationInterface;
use Solspace\Freeform\Records\CrmFieldRecord;

class CrmService extends IntegrationsService
{
    /**
     * @return FieldObject[]
     */
    public function getFields(
        CRMIntegrationInterface $integration,
        string $category,
        bool $refresh = false
    ): array {
        $existingRecords = CrmFieldRecord::find()
            ->where([
                'integrationId' => $integration->getId(),
                'category' => $category,
            ])
            ->indexBy('handle')
            ->all()
        ;

        if ($refresh || empty($existingRecords)) {
            $client = $this->clientProvider->getAuthorizedClient($integration);
            $logger = $this->loggerProvider->getLogger($integration);

            $fields = $integration->fetchFields($category, $client);
            $logger->debug(\sprintf('Fetched %d Fields', \count($fields)), ['category' => $category]);

            $usedFields = [];
            $newFields = [];
            foreach ($fields as $field) {
                if (!\array_key_exists($field->getHandle(), $existingRecords)) {
                    $newFields[] = $field;
                }

                $usedFields[$field->getHandle()] = $field;
            }

            foreach ($newFields as $field) {
                $record = new CrmFieldRecord();
                $record->integrationId = $integration->getId();
                $record->handle = $field->getHandle();
                $record->label = $field->getLabel();
                $record->type = $field->getType();
                $record->required = $field->isRequired();
                $record->category = $category;
                $record->options = json_encode($field->getOptions()->getIterator()->getArrayCopy());
                $record->save();

                $existingRecords[$field->getHandle()] = $record;
            }

            foreach ($existingRecords as $handle => $record) {
                if (!\array_key_exists($handle, $usedFields)) {
                    $record->delete();
                    unset($existingRecords[$handle]);
                } else {
                    $field = $usedFields[$handle];
                    $record->label = $field->getLabel();
                    $record->type = $field->getType();
                    $record->required = $field->isRequired();
                    $record->category = $category;
                    $record->options = json_encode($field->getOptions()->getIterator()->getArrayCopy());
                    $record->save();
                }
            }
        }

        return array_map(
            fn (CrmFieldRecord $record) => new FieldObject(
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
