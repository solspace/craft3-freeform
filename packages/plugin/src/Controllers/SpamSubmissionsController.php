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

namespace Solspace\Freeform\Controllers;

use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Elements\SpamSubmission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Services\SubmissionsService;
use yii\web\Response;

class SpamSubmissionsController extends SubmissionsController
{
    public const SPAM_TEMPLATE_BASE_PATH = 'freeform/spam';

    public function getSubmissionsService(): SubmissionsService
    {
        return $this->getSpamSubmissionsService();
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();

        $id = $this->request->post('id');
        $this->getSubmissionsService()->delete(SpamSubmission::find()->id($id));

        return $this->asJson(['success' => true]);
    }

    public function actionAllow()
    {
        $post = \Craft::$app->request->post();

        $submissionId = $post['submissionId'] ?? null;
        $model = $this->getSpamSubmissionsService()->getSubmissionById($submissionId);

        if (!$model) {
            throw new FreeformException(Freeform::t('Submission not found'));
        }

        if (!PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE)) {
            PermissionHelper::requirePermission(
                PermissionHelper::prepareNestedPermission(
                    Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                    $model->formId
                )
            );
        }

        $model->setFormFieldValues($post);
        $model->title = $post['title'] ?? $model->title;
        $model->statusId = $post['statusId'] ?? $model->statusId;

        if ($this->getSpamSubmissionsService()->allowSpamSubmission($model)) {
            // Return JSON response if the request is an AJAX request
            if (\Craft::$app->request->isAjax) {
                return $this->asJson(['success' => true]);
            }

            \Craft::$app->session->setNotice(Freeform::t('Submission updated'));
            \Craft::$app->session->setFlash(Freeform::t('Submission updated'), true);

            return $this->redirectToPostedUrl($model);
        }

        // Return JSON response if the request is an AJAX request
        if (\Craft::$app->request->isAjax) {
            return $this->asJson(['success' => false]);
        }

        \Craft::$app->session->setError(Freeform::t('Submission could not be updated'));

        // Send the event back to the template
        \Craft::$app->urlManager->setRouteParams(
            [
                'submission' => $model,
                'errors' => $model->getErrors(),
            ]
        );
    }

    protected function getTemplateBasePath(): string
    {
        return self::SPAM_TEMPLATE_BASE_PATH;
    }
}
