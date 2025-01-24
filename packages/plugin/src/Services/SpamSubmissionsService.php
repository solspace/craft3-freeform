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
use craft\db\Table;
use craft\records\Element;
use Solspace\Freeform\Elements\SpamSubmission;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Database\SpamSubmissionHandlerInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;

class SpamSubmissionsService extends SubmissionsService implements SpamSubmissionHandlerInterface
{
    public function getSubmissionById($id): ?Submission
    {
        return SpamSubmission::find()->id($id)->one();
    }

    /**
     * Takes spam submission, converts it to non-spam submission and runs all necessary processes
     * for non-spam submission.
     */
    public function allowSpamSubmission(SpamSubmission $submission): bool
    {
        $submission->isSpam = false;
        \Craft::$app->elements->saveElement($submission);

        // HACK: this is dirty, but I wasn't able to find better way to
        //      quickly convert SpamSubmission to Submission
        $element = Element::findOne($submission->id);
        $element->type = Submission::class;
        $element->save(false);

        \Craft::$app->cache->flush();

        $form = $submission->getForm();

        $layout = $form->getLayout();
        $integrationsQueue = Freeform::getInstance()->integrationsQueue;
        $tasks = $integrationsQueue->getTasksBySubmissionId($submission->id);
        $fields = [];
        foreach ($tasks as $task) {
            $fields[] = $layout->getFieldByHash($task->fieldHash);
        }

        foreach ($submission->getForm()->getLayout()->getFields() as $field) {
            $handle = $field->getHandle();
            if (!$handle || $field instanceof NoStorageInterface) {
                continue;
            }

            try {
                $field->setValue($submission->{$field->getHandle()}->getValue());
            } catch (\Exception $exception) {
            }
        }

        Freeform::getInstance()->submissions->postProcessSubmission($form, $submission, $fields);

        $integrationsQueue->deleteTasksBySubmissionId($submission->id);

        return true;
    }

    /**
     * Processes spam submission, so it could be processed normally in case of allowing.
     */
    public function postProcessSubmission(Form $form, Submission $submission, array $mailingListOptedInFields)
    {
        if (!$submission instanceof SpamSubmission || !$submission->id) {
            throw new FreeformException('Invalid $submission, can process only stored SpamSubmission instances.');
        }

        /**
         * Save payment info as if we convert/allow a spam submission to be changed to a submission,
         * the payment token value no longer exists when we trigger `submissionsService->postProcessSubmission` later on.
         */
        $freeform = Freeform::getInstance();

        $integrationsService = $freeform->integrations;
        $integrationsQueueService = $freeform->integrationsQueue;

        if ($integrationsService->processPayments($submission)) {
            $integrationsQueueService->enqueueIntegrations($submission, $mailingListOptedInFields);
        }
    }

    protected function getFindQuery(): Query
    {
        return SpamSubmission::find();
    }

    protected function findSubmissions(): Query
    {
        $submissionTable = Submission::TABLE;
        $elementTable = Table::ELEMENTS;

        return (new Query())
            ->from($submissionTable)
            ->innerJoin($elementTable, "{$elementTable}.[[id]] = {$submissionTable}.[[id]]")
            ->where([
                'isSpam' => true,
                "{$elementTable}.[[dateDeleted]]" => null,
            ])
        ;
    }
}
