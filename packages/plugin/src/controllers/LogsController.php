<?php

namespace Solspace\Freeform\controllers;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Resources\Bundles\LogBundle;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class LogsController extends BaseController
{
    public function actionIndex(): Response
    {
        $logReader = $this->getLoggerService()->getLogReader();

        $this->getLoggerService()->registerJsTranslations($this->view);

        return $this->renderTemplate(
            'freeform/logs/index',
            [
                'logReader' => $logReader,
            ]
        );
    }

    public function actionError(): Response
    {
        $logReader = $this->getLoggerService()->getLogReader();

        $this->getLoggerService()->registerJsTranslations($this->view);

        \Craft::$app->view->registerAssetBundle(LogBundle::class);

        return $this->renderTemplate(
            'freeform/logs/error',
            [
                'logReader' => $logReader,
            ]
        );
    }

    public function actionIntegrations(): Response
    {
        $logReader = $this->getLoggerService()->getLogReader(IntegrationLoggerProvider::LOG_FILE);

        $this->getLoggerService()->registerJsTranslations($this->view);

        \Craft::$app->view->registerAssetBundle(LogBundle::class);

        return $this->renderTemplate(
            'freeform/logs/error',
            [
                'logReader' => $logReader,
            ]
        );
    }

    public function actionClear(): Response
    {
        $this->requirePostRequest();

        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->getLoggerService()->clearLogs();

        if (\Craft::$app->request->getIsAjax()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirect('/');
    }
}
