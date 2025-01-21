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

namespace Solspace\Freeform\controllers;

use craft\records\Asset;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Assets\RegisterEvent;
use Solspace\Freeform\Events\Submissions\UpdateEvent;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Form\Layout\Page;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\SpamReason;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\SubmissionNoteRecord;
use Solspace\Freeform\Records\UnfinalizedFileRecord;
use Solspace\Freeform\Resources\Bundles\ExportButtonBundle;
use Solspace\Freeform\Resources\Bundles\SubmissionEditBundle;
use Solspace\Freeform\Resources\Bundles\SubmissionIndexBundle;
use yii\web\ForbiddenHttpException;
use yii\web\HttpException;
use yii\web\Response;

class SubmissionsController extends BaseController
{
    public const EVENT_BEFORE_UPDATE = 'beforeUpdate';
    public const EVENT_AFTER_UPDATE = 'afterUpdate';

    public const TEMPLATE_BASE_PATH = 'freeform/submissions';
    public const EVENT_REGISTER_INDEX_ASSETS = 'registerIndexAssets';
    public const EVENT_REGISTER_EDIT_ASSETS = 'registerEditAssets';

    public function actionIndex(?string $formHandle = null): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SUBMISSIONS_ACCESS);

        \Craft::$app->view->registerAssetBundle(SubmissionIndexBundle::class);

        $registerAssetsEvent = new RegisterEvent(\Craft::$app->view);
        $this->trigger(self::EVENT_REGISTER_INDEX_ASSETS, $registerAssetsEvent);

        if (PermissionHelper::checkPermission(Freeform::PERMISSION_ACCESS_QUICK_EXPORT)) {
            \Craft::$app->view->registerAssetBundle(ExportButtonBundle::class);
        }

        $forms = $this->getFormsService()->getAllForms();

        return $this->renderTemplate(
            $this->getTemplateBasePath(),
            [
                'forms' => $forms,
                'statuses' => $this->getStatusesService()->getAllStatuses(),
                'formHandle' => $formHandle,
                'spamReasons' => SpamReason::getReasons(),
            ]
        );
    }

    public function actionExport(): void
    {
        $this->requirePostRequest();
        $exportProfilesService = $this->getExportProfileService();

        $submissionIds = \Craft::$app->request->post('submissionIds');
        $submissionIds = explode(',', $submissionIds);

        /** @var SubmissionQuery $query */
        $query = Submission::find()->id($submissionIds);
        if (!$query->count()) {
            throw new FreeformException(Freeform::t('No submissions found'));
        }

        /** @var Submission $submission */
        $submission = $query->one();
        $form = $submission->getForm();

        $this->checkPermissions($form);

        $fieldDescriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('title', 'Title'))
            ->add(new FieldDescriptor('ip', 'IP Address'))
            ->add(new FieldDescriptor('dateCreated', 'Date Created'))
            ->add(new FieldDescriptor('status', 'Status'))
            ->add(new FieldDescriptor('userId', 'Author'))
        ;

        foreach ($submission->getFieldCollection() as $field) {
            $fieldDescriptors->add(new FieldDescriptor($field->getId(), $field->getLabel()));
        }

        $exporter = new ExportCsv($form, $query, $fieldDescriptors, $exportProfilesService->getExportSettings());

        $exportProfilesService->export($exporter, $form);
    }

    public function actionEdit(int $id): Response
    {
        $submissionsService = $this->getSubmissionsService();
        $submission = $submissionsService->getSubmissionById($id);

        if (!$submission) {
            throw new HttpException(404, Freeform::t('Submission with ID {id} not found', ['id' => $id]));
        }

        $noteRecord = SubmissionNoteRecord::findOne(['submissionId' => $id]);

        $title = $submission->title;

        if (!PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE)) {
            PermissionHelper::requirePermission(
                PermissionHelper::prepareNestedPermission(
                    Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                    $submission->formId
                )
            );
        }

        $this->view->registerAssetBundle(SubmissionEditBundle::class);
        $this->view->registerTranslations(Freeform::TRANSLATION_CATEGORY, [
            'Are you sure you want to delete this?',
        ]);

        $registerAssetsEvent = new RegisterEvent(\Craft::$app->view);
        $this->trigger(self::EVENT_REGISTER_EDIT_ASSETS, $registerAssetsEvent);

        $layout = $submission->getForm()->getLayout();

        $statuses = [];
        $statusModelList = Freeform::getInstance()->statuses->getAllStatuses();
        foreach ($statusModelList as $statusId => $status) {
            $statuses[$statusId] = $status;
        }

        $fieldRenderer = [$submissionsService, 'renderSubmissionField'];

        $tabs = array_reduce(
            array_map(
                fn (Page $page) => [
                    'tabId' => 'page'.$page->getIndex(),
                    'selected' => 0 === $page->getIndex(),
                    'url' => '#tab-'.$page->getIndex(),
                    'label' => $page->getLabel(),
                ],
                $layout->getPages()->getIterator()->getArrayCopy()
            ),
            function ($result, $item) {
                $result['page'.$item['tabId']] = $item;

                return $result;
            },
            []
        );

        $variables = [
            'form' => $submission->getForm(),
            'submission' => $submission,
            'layout' => $layout,
            'title' => $title,
            'statuses' => $statuses,
            'note' => $noteRecord?->note,
            'continueEditingUrl' => 'freeform/submissions/{id}',
            'fieldRenderer' => $fieldRenderer,
            'tabs' => $tabs,
            'sidebarHtml' => $submission->getSidebarHtml(true),
        ];

        return $this->renderTemplate(
            $this->getTemplateBasePath().'/edit',
            $variables
        );
    }

    public function actionSave()
    {
        $post = \Craft::$app->request->post();

        $submissionId = $post['submissionId'] ?? null;
        $model = $this->getSubmissionsService()->getSubmissionById($submissionId);

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

        $this->removeStaleAssets($model, $post);
        $post = $this->uploadAndAddFiles($model->getForm(), $post);

        $userId = \Craft::$app->request->post('author', $model->userId);
        if (\is_array($userId)) {
            $userId = reset($userId);
        }

        $title = \Craft::$app->request->post('title', $model->title);

        $model->title = $title;
        $model->userId = (int) $userId;
        $model->statusId = $post['statusId'];
        $model->setFormFieldValues($post);

        $event = new UpdateEvent($model, $model->getForm());
        $this->trigger(self::EVENT_BEFORE_UPDATE, $event);

        if ($event->isValid && \Craft::$app->getElements()->saveElement($model)) {
            // Update title for each site
            if ($model->getForm()->getSettings()->getGeneral()->translations) {
                $sites = \Craft::$app->sites->getAllSiteIds();
                foreach ($sites as $siteId) {
                    if ($siteId === \Craft::$app->sites->getCurrentSite()->id) {
                        continue;
                    }

                    $model->siteId = $siteId;
                    $model->title = $title;
                    \Craft::$app->elements->saveElement($model, true, false);
                }
            }

            $this->trigger(self::EVENT_AFTER_UPDATE, $event);

            // Return JSON response if the request is an AJAX request
            if (\Craft::$app->request->isAjax) {
                return $this->asJson(['success' => true]);
            }

            \Craft::$app->session->setSuccess(Freeform::t('Submission updated.'));
            \Craft::$app->session->setFlash(Freeform::t('Submission updated.'), true);

            return $this->redirectToPostedUrl($model);
        }

        // Return JSON response if the request is an AJAX request
        if (\Craft::$app->request->isAjax) {
            return $this->asJson(['success' => false]);
        }

        \Craft::$app->session->setError(Freeform::t('Submission could not be updated.'));

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
        return self::TEMPLATE_BASE_PATH;
    }

    private function checkPermissions(Form $form): void
    {
        $canManage = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
        $canManageSpecific = PermissionHelper::checkPermission(
            PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                $form->getId()
            )
        );

        $canRead = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_READ);
        $canReadSpecific = PermissionHelper::checkPermission(
            PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_READ,
                $form->getId()
            )
        );

        if (!$canRead && !$canReadSpecific && !$canManage && !$canManageSpecific) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }

    private function removeStaleAssets(Submission $submission, array $post = [])
    {
        $fields = $submission->getForm()->getLayout()->getFields(FileUploadField::class);
        foreach ($fields as $field) {
            $handle = $field->getHandle();
            $oldIds = $submission->{$handle}->getValue() ?? [];
            if (!\is_array($oldIds)) {
                $oldIds = empty($oldIds) ? [] : [$oldIds];
            }

            $postedIds = $post[$handle] ?? [];

            $staleIds = array_diff($oldIds, $postedIds);

            foreach ($staleIds as $id) {
                try {
                    $asset = Asset::find()->where(['id' => $id])->one();
                    if ($asset) {
                        $asset->delete();
                    }
                } catch (\Exception $e) {
                }
            }
        }
    }

    private function uploadAndAddFiles(Form $form, array $post = []): array
    {
        $uploadFields = $form->getLayout()->getFields(FileUploadField::class);

        foreach ($uploadFields as $field) {
            $response = Freeform::getInstance()->files->uploadFile($field, $form);
            if ($response) {
                if ($response->getAssetIds()) {
                    $handle = $field->getHandle();
                    if (isset($post[$handle])) {
                        if (!\is_array($post[$handle])) {
                            $post[$handle] = [$post[$handle]];
                        }

                        $post[$handle] = array_merge($post[$handle], $response->getAssetIds());
                    } else {
                        $post[$handle] = $response->getAssetIds();
                    }

                    $assetIds = $post[$handle];
                    $records = UnfinalizedFileRecord::findAll(['assetId' => $assetIds]);
                    foreach ($records as $record) {
                        $record->delete();
                    }
                }
            }
        }

        return $post;
    }
}
