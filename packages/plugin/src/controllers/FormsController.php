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

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Configuration\FreeformConfig;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Resources\Bundles\FreeformClientBundle;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class FormsController extends BaseController
{
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
