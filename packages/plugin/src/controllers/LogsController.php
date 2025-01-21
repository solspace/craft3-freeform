<?php

namespace Solspace\Freeform\controllers;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Resources\Bundles\LogBundle;
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
                'category' => 'integrations',
            ]
        );
    }

    public function actionClear(?string $category = null): Response
    {
        $this->requirePostRequest();

        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $fileName = match ($category) {
            'integrations' => IntegrationLoggerProvider::LOG_FILE,
            default => null,
        };

        $this->getLoggerService()->clearLogs($fileName);

        if (\Craft::$app->request->getIsAjax()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirect('/');
    }
}
