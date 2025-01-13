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
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Database\SpamSubmissionHandlerInterface;

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
        \Craft::$app->elements->saveElement($submission, false, false);

        // HACK: this is dirty, but I wasn't able to find better way to
        //      quickly convert SpamSubmission to Submission
        $element = Element::findOne($submission->id);
        $element->type = Submission::class;
        $element->save(false);

        \Craft::$app->cache->flush();

        $integrationsQueue = Freeform::getInstance()->integrationsQueue;

        foreach ($submission->getForm()->getLayout()->getFields() as $field) {
            $handle = $field->getHandle();
            if (!$handle || $field instanceof NoStorageInterface) {
                continue;
            }

            try {
                $field->setValue($submission->{$field->getHandle()}->getValue());
            } catch (\Exception) {
            }
        }

        $form = $submission->getForm();
        $form->setSubmission($submission);
        Freeform::getInstance()->submissions->postProcessSubmission($form, $submission);

        $integrationsQueue->deleteTasksBySubmissionId($submission->id);

        return true;
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
