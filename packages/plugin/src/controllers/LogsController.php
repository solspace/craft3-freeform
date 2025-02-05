<?php

namespace Solspace\Freeform\controllers;

use Solspace\Freeform\Bundles\Integrations\Providers\IntegrationLoggerProvider;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationLoggerProvider;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Resources\Bundles\LogBundle;
use yii\web\Response;

class LogsController extends BaseController
{
    public function actionIndex(): Response
    {
        return $this->actionError();
    }

    public function actionError(): Response
    {
        return $this->renderLogs();
    }

    public function actionIntegrations(): Response
    {
        return $this->renderLogs(IntegrationLoggerProvider::LOG_FILE, 'integrations');
    }

    public function actionEmails(): Response
    {
        return $this->renderLogs(NotificationLoggerProvider::LOG_FILE, 'emails');
    }

    public function actionClear(?string $category = null): Response
    {
        $this->requirePostRequest();

        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $fileName = match ($category) {
            'emails' => NotificationLoggerProvider::LOG_FILE,
            'integrations' => IntegrationLoggerProvider::LOG_FILE,
            default => null,
        };

        $this->getLoggerService()->clearLogs($fileName);

        if (\Craft::$app->request->getIsAjax()) {
            return $this->asJson(['success' => true]);
        }

        return $this->redirect('/');
    }

    private function renderLogs(?string $file = null, ?string $category = null): Response
    {
        \Craft::$app->view->registerAssetBundle(LogBundle::class);

        $logReader = $this->getLoggerService()->getLogReader($file);
        $this->getLoggerService()->registerJsTranslations($this->view);

        return $this->renderTemplate(
            'freeform/logs/error',
            [
                'logReader' => $logReader,
                'category' => $category,
            ]
        );
    }
}
