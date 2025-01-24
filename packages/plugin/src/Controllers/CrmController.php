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

use craft\helpers\UrlHelper;
use craft\web\Controller;
use GuzzleHttp\Exception\RequestException;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\Integrations\IntegrationException;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\CRM\CRMOAuthConnector;
use Solspace\Freeform\Models\IntegrationModel;
use Solspace\Freeform\Records\IntegrationRecord;
use Solspace\Freeform\Resources\Bundles\CrmBundle;
use Solspace\Freeform\Resources\Bundles\IntegrationsBundle;
use Solspace\Freeform\Services\CrmService;
use yii\web\HttpException;
use yii\web\Response;

class CrmController extends Controller
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
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $integrations = $this->getCRMService()->getAllIntegrations();

        \Craft::$app->view->registerAssetBundle(CrmBundle::class);

        return $this->renderTemplate(
            'freeform/settings/_crm',
            [
                'integrations' => $integrations,
                'providers' => $this->getCRMService()->getAllCRMServiceProviders(),
            ]
        );
    }

    public function actionCreate(): Response
    {
        $model = IntegrationModel::create(IntegrationRecord::TYPE_CRM);
        $title = Freeform::t('Add a CRM integration');

        return $this->renderEditForm($model, $title);
    }

    public function actionEdit($id = null, IntegrationModel $model = null): Response
    {
        if (null === $model) {
            if (is_numeric($id)) {
                $model = $this->getCRMService()->getIntegrationById($id);
            }

            if (!$model && $id) {
                $model = $this->getCRMService()->getIntegrationByHandle($id);
            }
        }

        if (!$model) {
            throw new HttpException(404, Freeform::t('CRM integration not found'));
        }

        return $this->renderEditForm($model, $model->name);
    }

    public function actionSave(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->requirePostRequest();

        $post = \Craft::$app->request->post();

        $id = $post['id'] ?? null;
        $model = $this->getNewOrExistingIntegration((int) $id);

        $isNew = !$model->id;

        $postedClass = $post['class'] ?? null;
        $model->class = $postedClass;

        $postedClassSettings = $post['settings'][$postedClass] ?? [];
        unset($post['settings']);

        $settingBlueprints = $this->getCRMService()->getCRMSettingBlueprints($postedClass);

        foreach ($postedClassSettings as $key => $value) {
            $isValueValid = false;

            foreach ($settingBlueprints as $blueprint) {
                if ($blueprint->getHandle() === $key) {
                    $isValueValid = true;

                    break;
                }
            }

            if (!$isValueValid) {
                unset($postedClassSettings[$key]);
            }
        }

        // Adding hidden stored settings to the list
        foreach ($model->getIntegrationObject()->getSettings() as $key => $value) {
            if (!isset($postedClassSettings[$key])) {
                $postedClassSettings[$key] = $value;
            }
        }

        $post['settings'] = $postedClassSettings ?: null;

        $model->setAttributes($post);

        try {
            $model->getIntegrationObject()->onBeforeSave($model);
        } catch (\Exception $e) {
            $model->addError('integration', $e->getMessage());
        }

        if (!$model->getErrors() && $this->getCRMService()->save($model)) {
            // If it's a new integration - we make the user complete OAuth2 authentication
            if ($isNew) {
                $model->getIntegrationObject()->initiateAuthentication();
            }

            // Return JSON response if the request is an AJAX request
            if (\Craft::$app->request->isAjax) {
                return $this->asJson(['success' => true]);
            }

            \Craft::$app->session->setNotice(Freeform::t('CRM Integration saved'));
            \Craft::$app->session->setFlash('CRM Integration saved');

            return $this->redirectToPostedUrl($model);
        }

        // Return JSON response if the request is an AJAX request
        if (\Craft::$app->request->isAjax) {
            return $this->asJson(['success' => false]);
        }

        \Craft::$app->session->setError(Freeform::t('CRM Integration not saved'));

        return $this->renderEditForm($model, $model->name);
    }

    public function actionCheckIntegrationConnection(): Response
    {
        $id = \Craft::$app->request->post('id');

        /** @var AbstractCRMIntegration $integration */
        $integration = $this->getCRMService()->getIntegrationObjectById((int) $id);

        try {
            if ($integration->checkConnection()) {
                return $this->asJson(['success' => true]);
            }

            return $this->asJson(['success' => false]);
        } catch (RequestException $e) {
            return $this->asJson(['success' => false, 'errors' => [$e->getMessage()]]);
        } catch (\Exception $e) {
            return $this->asJson(['success' => false, 'errors' => [$e->getMessage()]]);
        }
    }

    public function actionForceAuthorization(string $handle)
    {
        $model = $this->getCRMService()->getIntegrationByHandle($handle);

        if (!$model) {
            throw new IntegrationException(
                Freeform::t(
                    "CRM integration with handle '{handle}' not found",
                    ['handle' => $handle]
                )
            );
        }

        $integration = $model->getIntegrationObject();
        $integration->initiateAuthentication();

        if ($integration->isAccessTokenUpdated()) {
            $this->getCRMService()->updateAccessToken($integration);
        }

        $this->redirect(UrlHelper::cpUrl('freeform/settings/crm/'.$model->id));
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $id = \Craft::$app->request->post('id');
        $this->getCRMService()->delete($id);

        return $this->asJson(['success' => true]);
    }

    private function renderEditForm(IntegrationModel $model, string $title): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $this->view->registerAssetBundle(IntegrationsBundle::class);

        if (\Craft::$app->request->getParam('code')) {
            $this->handleAuthorization($model);
        }

        $serviceProviderTypes = $this->getCRMService()->getAllCRMServiceProviders();
        $settingBlueprints = $this->getCRMService()->getAllCRMSettingBlueprints();
        $readmes = $this->getCRMService()->getAllReadmes();

        $variables = [
            'integration' => $model,
            'blockTitle' => $title,
            'serviceProviderTypes' => $serviceProviderTypes,
            'readmes' => $readmes,
            'continueEditingUrl' => 'freeform/settings/crm/{id}',
            'settings' => $settingBlueprints,
        ];

        return $this->renderTemplate('freeform/settings/_crm_edit', $variables);
    }

    private function getCRMService(): CrmService
    {
        return Freeform::getInstance()->crm;
    }

    private function getNewOrExistingIntegration(int $id = null): IntegrationModel
    {
        if (null === $id) {
            $model = null;
        } else {
            $model = $this->getCRMService()->getIntegrationById($id);
        }

        if (!$model) {
            $model = IntegrationModel::create(IntegrationRecord::TYPE_CRM);
        }

        return $model;
    }

    private function handleAuthorization(IntegrationModel $model): void
    {
        $integration = $model->getIntegrationObject();
        $code = \Craft::$app->request->getParam('code');

        if (!$integration instanceof CRMOAuthConnector || empty($code)) {
            return;
        }

        $accessToken = $integration->fetchAccessToken();

        $model->accessToken = $accessToken;
        $model->settings = $integration->getSettings();

        if ($this->getCRMService()->save($model)) {
            // Return JSON response if the request is an AJAX request
            \Craft::$app->session->setNotice(Freeform::t('CRM Integration saved'));
            \Craft::$app->session->setFlash('CRM Integration saved');

            $this->redirect(UrlHelper::cpUrl('freeform/settings/crm/'.$model->id));

            return;
        }

        \Craft::$app->session->setError(Freeform::t('CRM Integration not saved'));

        $this->redirect(UrlHelper::cpUrl('freeform/settings/crm/'.$model->id));
    }
}
