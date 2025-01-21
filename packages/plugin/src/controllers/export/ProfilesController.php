<?php

namespace Solspace\Freeform\controllers\export;

use craft\helpers\Cp;
use craft\helpers\UrlHelper;
use Solspace\Freeform\Controllers\BaseController;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Models\Pro\ExportProfileModel;
use Solspace\Freeform\Resources\Bundles\ExportProfileBundle;
use Solspace\Freeform\Resources\Bundles\SettingsBundle;
use yii\web\HttpException;
use yii\web\Response;

class ProfilesController extends BaseController
{
    public function actionIndex(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_ACCESS);
        $this->view->registerAssetBundle(SettingsBundle::class);

        $site = SitesHelper::getCurrentCpSite();
        $sites = SitesHelper::getEditableSites();
        $forms = $this->getFormsService()->getAllForms(sites: $site?->handle);
        $formIds = array_map(fn (Form $form) => $form->getId(), $forms);

        $exportProfileService = $this->getExportProfileService();
        $exportProfiles = $exportProfileService->getAllProfiles();

        $exportProfiles = array_filter(
            $exportProfiles,
            fn (ExportProfileModel $profile) => \in_array($profile->formId, $formIds)
        );

        $isCraft5 = version_compare(\Craft::$app->getVersion(), '5.0.0', '>=');

        $crumbs = [
            ['label' => Freeform::getInstance()->name, 'url' => UrlHelper::cpUrl('freeform')],
            ['label' => Freeform::t('Export'), 'url' => UrlHelper::cpUrl('freeform/export/profiles')],
            ['label' => Freeform::t('Profiles'), 'url' => UrlHelper::cpUrl('freeform/export/profiles')],
        ];

        if ($isCraft5 && $site && \Craft::$app->getIsMultiSite()) {
            array_unshift($crumbs, [
                'id' => 'site-crumb',
                'icon' => Cp::earthIcon(),
                'label' => \Craft::t('site', $site->name),
                'menu' => [
                    'label' => \Craft::t('site', 'Select site'),
                    'items' => Cp::siteMenuItems($sites, $site),
                ],
            ]);
        }

        return $this->renderTemplate(
            'freeform/export/profiles',
            [
                'exportProfiles' => $exportProfiles,
                'exporters' => $exportProfileService->getExporterTypes(),
                'selectedSite' => $site,
                'selectableSites' => $sites,
                'isCraft5' => $isCraft5,
                'forms' => $forms,
                'crumbs' => $crumbs,
                'showSiteMenu' => true,
            ]
        );
    }

    public function actionCreate(string $formHandle): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_MANAGE);

        $form = $this->getFormsService()->getFormByHandle($formHandle);
        if (!$form) {
            throw new HttpException(
                404,
                Freeform::t('Form with handle {handle} not found'),
                ['handle' => $formHandle]
            );
        }

        $profile = ExportProfileModel::create($form);

        return $this->renderEditForm($profile, Freeform::t('Create a new Export Profile'));
    }

    public function actionEdit(int $id): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_MANAGE);

        $profile = $this->getExportProfileService()->getProfileById($id);

        if (!$profile) {
            throw new HttpException(
                404,
                Freeform::t('Profile with ID {id} not found'),
                ['id' => $id]
            );
        }

        return $this->renderEditForm($profile, $profile->name);
    }

    public function actionSave(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_MANAGE);

        $post = \Craft::$app->request->post();

        $formId = \Craft::$app->request->post('formId');
        $form = $this->getFormsService()->getFormById($formId);

        if (!$form) {
            throw new HttpException(Freeform::t('Form with ID {id} not found', ['id' => $formId]));
        }

        $profileId = \Craft::$app->request->post('profileId');
        $profile = $this->getNewOrExistingProfile($profileId, $form);

        $profile->setAttributes($post);

        $profile->fields = $post['fieldSettings'];
        $profile->filters = $post['filters'] ?? [];

        if ($this->getExportProfileService()->save($profile)) {
            // Return JSON response if the request is an AJAX request
            if (\Craft::$app->request->isAjax) {
                return $this->asJson(['success' => true]);
            }

            \Craft::$app->session->setSuccess(Freeform::t('Export Profile saved.'));
            \Craft::$app->session->setFlash(Freeform::t('Export Profile saved.'), true);

            return $this->redirectToPostedUrl($profile);
        }

        // Return JSON response if the request is an AJAX request
        if (\Craft::$app->request->isAjax) {
            return $this->asJson(['success' => false]);
        }

        \Craft::$app->session->setError(Freeform::t('Export Profile not saved.'));

        return $this->renderEditForm($profile, $profile->name ?? '');
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_MANAGE);

        $profileId = \Craft::$app->request->post('id');

        $this->getExportProfileService()->deleteById($profileId);

        return $this->asJson(['success' => true]);
    }

    public function actionExport(): void
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_EXPORT_PROFILES_ACCESS);

        $this->requirePostRequest();

        $profileId = \Craft::$app->request->post('profileId');
        $type = \Craft::$app->request->post('type');

        $exportProfilesService = $this->getExportProfileService();

        $profile = $exportProfilesService->getProfileById($profileId);

        if (!$profile) {
            throw new HttpException(404, Freeform::t('Profile with ID {id} not found'), ['id' => $profileId]);
        }

        $form = $profile->getForm();
        $query = $profile->getQuery();
        $descriptors = $profile->getFieldDescriptors();

        $exporter = $exportProfilesService->createExporter($type, $form, $query, $descriptors);

        $exportProfilesService->export($exporter, $form);
    }

    private function renderEditForm(ExportProfileModel $model, string $title): Response
    {
        $this->view->registerAssetBundle(ExportProfileBundle::class);

        $title .= " ({$model->getForm()->getName()})";

        return $this->renderTemplate(
            'freeform/export/profiles/edit',
            [
                'profile' => $model,
                'title' => $title,
                'formOptionList' => $this->getFormsService()->getAllFormNames(),
                'statusOptionList' => $this->getStatusesService()->getAllStatusNames(),
                'continueEditingUrl' => 'freeform/export/profiles/{id}',
                'crumbs' => [
                    ['label' => 'Freeform', 'url' => UrlHelper::cpUrl('freeform')],
                    [
                        'label' => Freeform::t('Export Profiles'),
                        'url' => UrlHelper::cpUrl('freeform/export/profiles'),
                    ],
                ],
            ]
        );
    }

    private function getNewOrExistingProfile($id, Form $form): ExportProfileModel
    {
        $profile = $this->getExportProfileService()->getProfileById((int) $id);

        if (!$profile) {
            $profile = ExportProfileModel::create($form);
        }

        return $profile;
    }
}
