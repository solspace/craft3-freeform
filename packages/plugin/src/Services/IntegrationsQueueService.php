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

namespace Solspace\Freeform\Services;

use craft\db\Query;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\MailingListField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Models\IntegrationsQueueModel;
use Solspace\Freeform\Records\IntegrationsQueueRecord;

class IntegrationsQueueService extends BaseService
{
    /**
     * Stores necessary additional form data, so integrations could be run asynchronously.
     *
     * @param AbstractField[] $fields
     */
    public function enqueueIntegrations(Submission $submission, array $fields)
    {
        $this->enqueueMailingListIntegrations($submission, $fields);
    }

    /**
     * Stores necessary form data, so addition to mailing lists could be run asynchronously.
     *
     * @param AbstractField[] $fields
     */
    public function enqueueMailingListIntegrations(Submission $submission, array $fields)
    {
        foreach ($fields as $field) {
            if (!$field instanceof MailingListField) {
                continue;
            }

            $isSelected = $field->isHidden() || (bool) $field->getValue();
            if ($isSelected && $field->getEmailFieldHash() && $field->getResourceId()) {
                $task = new IntegrationsQueueModel();
                $task->integrationType = IntegrationsQueueRecord::INTEGRATION_TYPE_MAILING_LIST;
                $task->status = IntegrationsQueueRecord::STATUS_HALTED;
                $task->submissionId = $submission->id;
                $task->fieldHash = $field->getHash();
                $this->save($task);
            }
        }
    }

    /**
     * Process submission integrations that were queued for asynchronously processing.
     *
     * @param int $submissionId
     */
    public function processIntegrations($submissionId)
    {
        $tasks = $this->getTasksBySubmissionId($submissionId);
        foreach ($tasks as $task) {
            $this->processMailingListIntegration($task);
        }
    }

    /**
     * Process submission mailing lists integrations that were queued for asynchronously processing.
     */
    public function processMailingListIntegration(IntegrationsQueueModel $task)
    {
        Freeform::getInstance()->mailingLists->addToMailingList($task);
    }

    /**
     * Stores integration task to DB.
     *
     * @return bool
     */
    public function save(IntegrationsQueueModel $model)
    {
        $isNew = !$model->id;

        if (!$isNew) {
            $record = IntegrationsQueueRecord::findOne(['id' => $model->id]);
        } else {
            $record = new IntegrationsQueueRecord();
        }

        $record->integrationType = $model->integrationType;
        $record->status = $model->status;
        $record->submissionId = $model->submissionId;
        $record->fieldHash = $model->fieldHash;

        $record->validate();
        $model->addErrors($record->getErrors());

        if (!$model->hasErrors()) {
            $transaction = \Craft::$app->getDb()->getTransaction() ?? \Craft::$app->getDb()->beginTransaction();

            try {
                $record->save(false);

                if ($isNew) {
                    $model->id = $record->id;
                }

                if (null !== $transaction) {
                    $transaction->commit();
                }

                return true;
            } catch (\Exception $e) {
                if (null !== $transaction) {
                    $transaction->rollBack();
                }

                throw $e;
            }
        }

        return false;
    }

    /**
     * Gets integrations task from DB.
     *
     * @param int $submissionId
     *
     * @return IntegrationsQueueModel[]
     */
    public function getTasksBySubmissionId($submissionId)
    {
        $result = $this->getIntegrationQueueQuery()
            ->where(['submissionId' => $submissionId])
            ->all()
        ;

        return array_map([$this, 'createTask'], $result);
    }

    /**
     * Deletes integration task from DB.
     *
     * @param mixed $submissionId
     *
     * @return bool
     */
    public function deleteTasksBySubmissionId($submissionId)
    {
        try {
            \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(IntegrationsQueueRecord::TABLE, ['submissionId' => $submissionId])
                ->execute();
        } catch (\yii\db\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * @return Query
     */
    protected function getIntegrationQueueQuery()
    {
        return (new Query())
            ->select(
                [
                    'integrations_queue.id',
                    'integrations_queue.submissionId',
                    'integrations_queue.fieldHash',
                    'integrations_queue.integrationType',
                    'integrations_queue.status',
                ]
            )
            ->from(IntegrationsQueueRecord::TABLE.' integrations_queue')
        ;
    }

    /**
     * @param mixed $data
     *
     * @return IntegrationsQueueModel
     */
    protected function createTask($data)
    {
        return new IntegrationsQueueModel($data);
    }
}
