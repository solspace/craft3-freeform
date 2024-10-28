<?php

namespace Solspace\Freeform\controllers\pdf;

use Solspace\Freeform\controllers\BaseController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\PdfTemplateRecord;
use Solspace\Freeform\Resources\Bundles\PdfTemplateEditorBundle;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PdfTemplatesController extends BaseController
{
    public function init(): void
    {
        if (!\Craft::$app->request->getIsConsoleRequest()) {
            $this->requireLogin();
        }

        parent::init();
    }

    public function actionIndex(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_PDF_TEMPLATES_ACCESS);

        $templates = PdfTemplateRecord::find()
            ->select(['id', 'name', 'description', 'fileName'])
            ->orderBy(['sortOrder' => \SORT_ASC])
            ->indexBy('id')
            ->all()
        ;

        return $this->renderTemplate(
            'freeform/pdf-templates',
            [
                'title' => Freeform::t('PDF Templates'),
                'templates' => $templates,
            ]
        );
    }

    public function actionCreate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_PDF_TEMPLATES_MANAGE);

        $record = new PdfTemplateRecord();

        return $this->renderEditForm($record);
    }

    public function actionEdit(int $id): Response
    {
        $record = $this->getNewOrExistingRecord($id);
        if (!$record->id) {
            throw new NotFoundHttpException(Freeform::t('PDF Template not found'));
        }

        return $this->renderEditForm($record);
    }

    public function actionSave(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_PDF_TEMPLATES_MANAGE);
        $this->requirePostRequest();

        $post = \Craft::$app->request->post();

        $id = $post['id'] ?? null;
        $record = $this->getNewOrExistingRecord($id);

        $record->setAttributes($post);
        $record->save();

        $isAjax = \Craft::$app->request->isAjax;
        $session = \Craft::$app->session;

        if ($record->hasErrors()) {
            if ($isAjax) {
                return $this->asJson(['success' => false]);
            }

            $session->setError(Freeform::t('Integration not saved.'));

            return $this->renderEditForm($record);
        }

        if ($isAjax) {
            return $this->asJson(['success' => true]);
        }

        $session->setSuccess(Freeform::t('Integration saved.'));

        return $this->redirectToPostedUrl($record);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_PDF_TEMPLATES_MANAGE);

        $id = \Craft::$app->request->post('id');
        $record = PdfTemplateRecord::findOne($id);
        $record?->delete();

        return $this->asJson(['success' => true]);
    }

    protected function renderEditForm(PdfTemplateRecord $record): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_PDF_TEMPLATES_MANAGE);

        $this->view->registerAssetBundle(PdfTemplateEditorBundle::class);

        $variables = [
            'template' => $record,
            'continueEditingUrl' => 'freeform/settings/pdf-templates/{id}',
            'action' => 'freeform/pdf-templates/save',
            'title' => $record->name,
        ];

        return $this->renderTemplate('freeform/pdf-templates/edit', $variables);
    }

    protected function getNewOrExistingRecord(?int $id): PdfTemplateRecord
    {
        $record = PdfTemplateRecord::findOne($id);
        if (!$record) {
            $record = new PdfTemplateRecord();
            $record->name = 'New PDF Template';
        }

        return $record;
    }
}
