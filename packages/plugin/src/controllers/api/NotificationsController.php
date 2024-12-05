<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\controllers\api;

use Solspace\Freeform\Bundles\Notifications\Providers\NotificationTemplateProvider;
use Solspace\Freeform\Bundles\Notifications\Providers\NotificationTypesProvider;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Library\Exceptions\Notifications\NotificationException;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Services\SettingsService;
use Symfony\Component\Serializer\Serializer;
use yii\web\Response;

class NotificationsController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private NotificationTypesProvider $notificationTypesProvider,
        private NotificationTemplateProvider $notificationTemplateProvider,
        private SettingsService $settingsService,
        private Serializer $serializer,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionGetTypes(): Response
    {
        $types = $this->notificationTypesProvider->getTypes();

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->content = $this->serializer->serialize($types, 'json');

        return $response;
    }

    public function actionGetTemplates(): Response
    {
        if ('POST' === $this->request->method) {
            return $this->createNewTemplate();
        }

        $database = $this->notificationTemplateProvider->getDatabaseTemplates();
        $file = $this->notificationTemplateProvider->getFileTemplates();

        $settings = $this->settingsService->getSettingsModel();

        $allowedTypes = [];

        switch ($settings->emailTemplateStorageType) {
            case Settings::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE:
                $allowedTypes[] = Settings::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE;

                break;

            case Settings::EMAIL_TEMPLATE_STORAGE_TYPE_FILES:
                $allowedTypes[] = Settings::EMAIL_TEMPLATE_STORAGE_TYPE_FILES;

                break;

            case Settings::EMAIL_TEMPLATE_STORAGE_TYPE_BOTH:
            default:
                $allowedTypes[] = Settings::EMAIL_TEMPLATE_STORAGE_TYPE_DATABASE;
                $allowedTypes[] = Settings::EMAIL_TEMPLATE_STORAGE_TYPE_FILES;

                break;
        }

        $content = [
            'allowedTypes' => $allowedTypes,
            'default' => $settings->emailTemplateDefault,
            'templates' => [
                'database' => $database,
                'files' => $file,
            ],
        ];

        $response = new Response();
        $response->format = Response::FORMAT_JSON;
        $response->content = $this->serializer->serialize($content, 'json');

        return $response;
    }

    private function createNewTemplate(): Response
    {
        $request = $this->request;
        $errors = [];

        $name = $request->post('name');

        if (!$name) {
            $errors[] = Freeform::t('Name is required');
        }

        $record = null;
        $iterator = 1;

        do {
            try {
                $record = $this->getNotificationsService()->create($name);
            } catch (NotificationException $e) {
                switch ($e->getCode()) {
                    case NotificationException::NO_EMAIL_DIR:
                    case NotificationException::NO_CONTENT:
                        $errors[] = $e->getMessage();

                        break 2;
                }
            }

            $name = preg_replace('/\s\d+$/', '', $name);
            $name = $name.' '.$iterator++;
        } while (!$record);

        if ($errors) {
            $this->response->statusCode = 405;

            return $this->asJson(['errors' => $errors]);
        }

        $notification = NotificationTemplate::fromRecord($record);

        $this->response->format = Response::FORMAT_JSON;
        $this->response->content = $this->serializer->serialize($notification, 'json');

        return $this->response;
    }
}
