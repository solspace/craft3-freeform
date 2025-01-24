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

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Models\Settings;
use Solspace\Freeform\Resources\Bundles\CodepackBundle;
use Solspace\Freeform\Resources\Bundles\SettingsBundle;
use Solspace\Freeform\Services\SettingsService;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class SettingsController extends BaseController
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
        if ($this->getSettingsService()->isAllowAdminEdit()) {
            return $this->actionProvideSetting();
        }

        return $this->redirect(UrlHelper::cpUrl('freeform/settings/statuses'));
    }

    public function actionDefaultView(): Response
    {
        if ($this->getSettingsService()->isOldFreeformInstalled()) {
            throw new FreeformException(
                Freeform::t('We’ve detected that both the new 3.x and old 2.x versions of Freeform are installed. Please review the Upgrading from Freeform 2.x guide and remove the old Freeform Pro 2.x plugin to resolve this issue.')
            );
        }

        $defaultView = $this->getSettingsModel()->defaultView;

        $canAccessDashboard = PermissionHelper::checkPermission(Freeform::PERMISSION_DASHBOARD_ACCESS);
        $canAccessForms = PermissionHelper::checkPermission(Freeform::PERMISSION_FORMS_ACCESS);
        $canAccessSubmissions = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_ACCESS);
        $canAccessFields = PermissionHelper::checkPermission(Freeform::PERMISSION_FIELDS_ACCESS);
        $canAccessNotifications = PermissionHelper::checkPermission(Freeform::PERMISSION_NOTIFICATIONS_ACCESS);
        $canAccessSettings = PermissionHelper::checkPermission(Freeform::PERMISSION_SETTINGS_ACCESS);
        $canAccessResources = PermissionHelper::checkPermission(Freeform::PERMISSION_RESOURCES);

        $isDashboardView = Freeform::VIEW_DASHBOARD === $defaultView;
        $isFormView = Freeform::VIEW_FORMS === $defaultView;
        $isSubmissionView = Freeform::VIEW_SUBMISSIONS === $defaultView;

        $cantAccessFormView = $isFormView && !$canAccessForms;
        $cantAccessSubmissionView = $isSubmissionView && !$canAccessSubmissions;
        $cantAccessDashboardView = $isDashboardView && !$canAccessDashboard;
        if ($cantAccessFormView || $cantAccessSubmissionView || $cantAccessDashboardView) {
            if ($canAccessDashboard) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_DASHBOARD));
            }

            if ($canAccessForms) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_FORMS));
            }

            if ($canAccessSubmissions) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_SUBMISSIONS));
            }

            if ($canAccessFields) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_FIELDS));
            }

            if ($canAccessNotifications) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_NOTIFICATIONS));
            }

            if ($canAccessSettings) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_SETTINGS));
            }

            if (Freeform::getInstance()->isPro() && PermissionHelper::checkPermission(Freeform::PERMISSION_EXPORT_PROFILES_ACCESS)) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_EXPORT_PROFILES));
            }

            if ($canAccessResources) {
                return $this->redirect(UrlHelper::cpUrl('freeform/'.Freeform::VIEW_RESOURCES));
            }
        }

        return $this->redirect(UrlHelper::cpUrl("freeform/{$defaultView}"));
    }

    public function actionAddDemoTemplate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->requirePostRequest();

        $errors = [];
        $settings = $this->getSettingsModel();
        $extension = '.twig';

        $templateDirectory = $settings->getAbsoluteFormTemplateDirectory();
        $templateName = \Craft::$app->request->post('templateName', null);

        if (!$templateDirectory) {
            $errors[] = Freeform::t('No custom template directory specified in settings');
        } else {
            if ($templateName) {
                $templateName = StringHelper::toSnakeCase($templateName);

                $templatePath = $templateDirectory.'/'.$templateName.$extension;
                if (file_exists($templatePath)) {
                    $errors[] = Freeform::t("Template '{name}' already exists", ['name' => $templateName.$extension]);
                } else {
                    try {
                        FileHelper::writeToFile($templatePath, $settings->getDemoTemplateContent());
                    } catch (FreeformException $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } else {
                $errors[] = Freeform::t('No template name specified');
            }
        }

        return $this->asJson(
            [
                'templateName' => $templateName.$extension,
                'errors' => $errors,
            ]
        );
    }

    public function actionAddEmailTemplate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->requirePostRequest();

        $errors = [];
        $settings = $this->getSettingsModel();
        $extension = '.twig';

        $templateDirectory = $settings->getAbsoluteEmailTemplateDirectory();
        $templateName = \Craft::$app->request->post('templateName');

        if (!$templateDirectory) {
            $errors[] = Freeform::t('No custom template directory specified in settings');
        } else {
            if ($templateName) {
                $templateName = StringHelper::toSnakeCase($templateName);

                $templatePath = $templateDirectory.'/'.$templateName.$extension;
                if (file_exists($templatePath)) {
                    $errors[] = Freeform::t("Template '{name}' already exists", ['name' => $templateName.$extension]);
                } else {
                    try {
                        FileHelper::writeToFile($templatePath, $settings->getEmailTemplateContent());
                    } catch (FreeformException $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } else {
                $errors[] = Freeform::t('No template name specified');
            }
        }

        return $this->asJson([
            'templateName' => $templateName,
            'errors' => $errors,
        ]);
    }

    public function actionAddSuccessTemplate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->requirePostRequest();

        $errors = [];
        $settings = $this->getSettingsModel();
        $extension = '.twig';

        $templateDirectory = $settings->getAbsoluteSuccessTemplateDirectory();
        $templateName = \Craft::$app->request->post('templateName');

        if (!$templateDirectory) {
            $errors[] = Freeform::t('No success template directory specified in settings');
        } else {
            if ($templateName) {
                $templateName = StringHelper::toSnakeCase($templateName);

                $templatePath = $templateDirectory.'/'.$templateName.$extension;
                if (file_exists($templatePath)) {
                    $errors[] = Freeform::t("Template '{name}' already exists", ['name' => $templateName.$extension]);
                } else {
                    try {
                        FileHelper::writeToFile($templatePath, $settings->getSuccessTemplateContent());
                    } catch (FreeformException $exception) {
                        $errors[] = $exception->getMessage();
                    }
                }
            } else {
                $errors[] = Freeform::t('No template name specified');
            }
        }

        return $this->asJson(
            [
                'templateName' => $templateName,
                'errors' => $errors,
            ]
        );
    }

    public function actionSaveSettings()
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->requirePostRequest();
        $postData = \Craft::$app->request->post('settings', []);

        if ($this->getSettingsService()->saveSettings($postData)) {
            \Craft::$app->session->setNotice(Freeform::t('Settings Saved'));

            if (isset($postData['purgableSubmissionAgeInDays']) || isset($postData['purgableSpamAgeInDays'])) {
                \Craft::$app->cache->delete(SettingsService::CACHE_KEY_PURGE);
            }

            return $this->redirectToPostedUrl();
        }

        $plugin = Freeform::getInstance();
        $errors = $plugin->getSettings()->getErrors();
        \Craft::$app->session->setError(
            implode("\n", \Solspace\Commons\Helpers\StringHelper::flattenArrayValues($errors))
        );
    }

    public function actionProvideSetting(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $section = \Craft::$app->request->getSegment(3);
        $settingsService = $this->getSettingsService();
        if (!$settingsService->isAllowAdminEdit() && $settingsService->isSectionASetting($section)) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $formattingTemplateList = [];
        if ($this->getSettingsService()->getSettingsModel()->defaultTemplates) {
            $formattingTemplateList[] = ['optgroup' => Freeform::t('Solspace Templates')];
            foreach ($this->getSettingsService()->getSolspaceFormTemplates() as $formTemplate) {
                $formattingTemplateList[] = [
                    'label' => $formTemplate->getName(),
                    'value' => $formTemplate->getFileName(),
                ];
            }
        }

        $formattingTemplateList[] = ['optgroup' => Freeform::t('Custom Templates')];
        foreach ($this->getSettingsService()->getCustomFormTemplates() as $formTemplate) {
            $formattingTemplateList[] = [
                'label' => $formTemplate->getName(),
                'value' => $formTemplate->getFileName(),
            ];
        }

        $this->view->registerAssetBundle(CodepackBundle::class);
        $this->view->registerAssetBundle(SettingsBundle::class);

        return $this->renderTemplate(
            'freeform/settings/'.($section ? '_'.(string) $section : ''),
            [
                'settings' => $this->getSettingsModel(),
                'formattingTemplateList' => $formattingTemplateList,
            ]
        );
    }

    private function getSettingsModel(): Settings
    {
        $settingsService = Freeform::getInstance()->settings;

        return $settingsService->getSettingsModel();
    }
}
