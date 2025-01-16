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

use craft\db\Table;
use Solspace\Freeform\Bundles\Fields\Types\FieldTypesProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Configuration\FreeformConfig;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Resources\Bundles\FreeformClientBundle;
use yii\db\Query;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends BaseController
{
    public function __construct($id, $module, $config, private FieldTypesProvider $fieldTypesProvider)
    {
        parent::__construct($id, $module, $config);
    }

    public function actionIndex(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $translations = include __DIR__.'/../translations/en/freeform.php';
        $translations = array_keys($translations);

        $this->view->registerAssetBundle(FreeformClientBundle::class);
        $this->view->registerTranslations(Freeform::TRANSLATION_CATEGORY, $translations);

        $config = \Craft::$container->get(FreeformConfig::class);

        return $this->renderTemplate('freeform/forms', [
            'config' => $config,
        ]);
    }

    public function actionResetSpamCounter(): Response
    {
        $this->requirePostRequest();

        $formId = (int) \Craft::$app->request->post('formId');
        $this->requireFormManagePermission($formId);

        if (!$formId) {
            return $this->asErrorJson(Freeform::t('No form ID specified'));
        }

        try {
            \Craft::$app
                ->getDb()
                ->createCommand()
                ->update(
                    FormRecord::TABLE,
                    ['spamBlockCount' => 0],
                    ['id' => $formId]
                )
                ->execute()
            ;
        } catch (\Exception $e) {
            return $this->asErrorJson($e->getMessage());
        }

        return $this->asJson(['success' => true]);
    }

    public function actionExport()
    {
        $this->requirePostRequest();
        $request = $this->request;
        $isCraft5 = version_compare(\Craft::$app->version, '5.0.0-alpha', '>=');

        $id = $request->post('id');
        $type = $request->post('type', 'csv');

        if (!Freeform::getInstance()->isPro()) {
            $type = 'csv';
        }

        $form = $this->getFormsService()->getFormById($id);
        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $canManageAll = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
        if (!$canManageAll) {
            PermissionHelper::requirePermission(
                PermissionHelper::prepareNestedPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE, $id)
            );
        }

        $selectFields = [];
        $selectFields[] = '[[s.id]]';
        $selectFields[] = '[[s.ip]]';
        $selectFields[] = '[[s.dateCreated]]';
        $selectFields[] = $isCraft5 ? '[[es.title]]' : '[[c.title]]';

        foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
            $fieldName = Submission::getFieldColumnName($field);
            $fieldHandle = $field->getHandle();

            $selectFields[] = "[[sc.{$fieldName}]] as {$fieldHandle}";
        }

        $query = (new Query())
            ->select($selectFields)
            ->from(Submission::TABLE.' s')
            ->innerJoin(Submission::getContentTableName($form).' sc', 'sc.[[id]] = s.[[id]]')
            ->where(['s.[[formId]]' => $id])
        ;

        if ($isCraft5) {
            $query->innerJoin('{{%elements_sites}} es', 'es.[[elementId]] = s.[[id]]');
        } else {
            $query->innerJoin('{{%content}} c', 'c.[[elementId]] = s.[[id]]');
        }

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
            $query->innerJoin(
                $elements.' e',
                'e.[[id]] = s.[[id]] AND e.[[dateDeleted]] IS NULL'
            );
        }

        $data = $query->all();

        $exporter = $this->getExportProfileService()->createExporter($type, $form, $data);

        $this->getExportProfileService()->export($exporter, $form);
    }

    private function requireFormManagePermission($id): void
    {
        $managePermission = Freeform::PERMISSION_FORMS_MANAGE;
        $nestedPermission = PermissionHelper::prepareNestedPermission($managePermission, $id);

        $canManageAll = PermissionHelper::checkPermission($managePermission);
        $canManageCurrent = PermissionHelper::checkPermission($nestedPermission);

        if (!$canManageAll && !$canManageCurrent) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }
}
