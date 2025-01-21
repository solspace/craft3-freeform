<?php

namespace Solspace\Freeform\controllers\export;

use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Controllers\BaseController;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\Pro\ExportSettingRecord;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class QuickExportController extends BaseController
{
    public function actionExportDialogue(): Response
    {
        $formId = \Craft::$app->request->getParam('formId');
        $isSpam = 'true' === \Craft::$app->request->getParam('isSpam');

        $allowedFormIds = $this->getSubmissionsService()->getAllowedReadFormIds();

        /** @var Form[] $forms */
        $forms = [];

        $fields = [];
        $forms = $this->getFormsService()->getAllForms();
        foreach ($forms as $form) {
            if (null !== $allowedFormIds) {
                if (!\in_array($form->getId(), $allowedFormIds)) {
                    continue;
                }
            }

            foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
                $fields[$field->getId()] = $field;
            }
        }

        $firstForm = reset($forms);
        $settingRecord = $this->getExportSettings();

        $setting = [];
        foreach ($forms as $form) {
            $storedFieldIds = $fieldSetting = [];
            if (!$settingRecord) {
                continue;
            }

            if (!$settingRecord->setting) {
                $settingRecord->setting = [];
            }

            $settingArray = JsonHelper::decode($settingRecord->setting, true);

            if ($settingRecord && isset($settingArray[$form->getId()])) {
                foreach ($settingArray[$form->getId()] as $fieldId => $item) {
                    $label = $item['label'];
                    $isChecked = (bool) $item['checked'];

                    if (is_numeric($fieldId)) {
                        $field = $form->get($fieldId);
                        if ($field) {
                            $label = $field->getLabel();

                            $storedFieldIds[] = $fieldId;

                            $fieldSetting[$fieldId] = [
                                'label' => $label,
                                'checked' => $isChecked,
                            ];
                        }
                    } else {
                        $fieldSetting[$fieldId] = [
                            'label' => $label,
                            'checked' => $isChecked,
                        ];
                    }
                }
            }

            if (empty($fieldSetting)) {
                $fieldSetting['id'] = [
                    'label' => 'ID',
                    'checked' => true,
                ];
                $fieldSetting['title'] = [
                    'label' => 'Title',
                    'checked' => true,
                ];
                $fieldSetting['ip'] = [
                    'label' => 'IP Address',
                    'checked' => true,
                ];
                $fieldSetting['status'] = [
                    'label' => 'Status',
                    'checked' => true,
                ];
                $fieldSetting['dateCreated'] = [
                    'label' => 'Date Created',
                    'checked' => true,
                ];
            }

            if (!isset($fieldSetting['userId'])) {
                $fieldSetting['userId'] = [
                    'label' => 'Author',
                    'checked' => true,
                ];
            }

            foreach ($form->getLayout()->getFields()->getStorableFields() as $field) {
                if (
                    !$field->getId()
                    || \in_array($field->getId(), $storedFieldIds, true)
                ) {
                    continue;
                }

                $fieldSetting[$field->getId()] = [
                    'label' => $field->getLabel(),
                    'checked' => true,
                ];
            }

            $formSetting['form'] = $form;
            $formSetting['fields'] = $fieldSetting;

            $setting[] = $formSetting;
        }

        $selectedFormId = null;
        if ($formId && isset($forms[$formId])) {
            $selectedFormId = $formId;
        } elseif ($firstForm) {
            $selectedFormId = $firstForm->getId();
        }

        return $this->renderTemplate(
            'freeform/_components/modals/export_csv',
            [
                'setting' => $setting,
                'forms' => $forms,
                'fields' => $fields,
                'selectedFormId' => $selectedFormId,
                'isSpam' => $isSpam,
                'exporters' => $this->getExportProfileService()->getExporterTypes(),
            ]
        );
    }

    /**
     * @throws InvalidConfigException
     * @throws FreeformException
     * @throws Exception
     * @throws ForbiddenHttpException
     * @throws BadRequestHttpException
     */
    public function actionIndex()
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_SUBMISSIONS_ACCESS);

        $isCraft5 = version_compare(\Craft::$app->version, '5.0.0-alpha', '>=');
        $settings = $this->getExportSettings();
        $exportProfilesService = $this->getExportProfileService();

        $formId = \Craft::$app->request->post('form_id');
        $exportType = \Craft::$app->request->post('export_type');
        $exportFields = \Craft::$app->request->post('export_fields');
        $isSpam = (bool) \Craft::$app->request->post('spam');

        $form = $this->getFormsService()->getFormById($formId);
        if (!$form) {
            return;
        }

        $canManage = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
        $canManageSpecific = PermissionHelper::checkPermission(
            PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                $formId
            )
        );

        $canRead = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_READ);
        $canReadSpecific = PermissionHelper::checkPermission(
            PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_READ,
                $formId
            )
        );

        if (!$canRead && !$canReadSpecific && !$canManage && !$canManageSpecific) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $fieldData = $exportFields[$form->getId()];

        $settings->setting = $exportFields;
        $settings->save();

        $collection = new FieldDescriptorCollection();

        foreach ($fieldData as $fieldId => $data) {
            $label = $data['label'];
            $isChecked = $data['checked'];

            if (is_numeric($fieldId)) {
                $field = $form->get($fieldId);
                if (!$field) {
                    continue;
                }

                $label = $field->getLabel();
            }

            $collection->add(
                new FieldDescriptor(
                    $fieldId,
                    $label,
                    $isChecked,
                )
            );
        }

        $query = Submission::find()
            ->formId($form->getId())
            ->isSpam($isSpam)
        ;

        $exporter = $exportProfilesService->createExporter($exportType, $form, $query, $collection);
        $exportProfilesService->export($exporter, $form);
    }

    private function getExportSettings(): ExportSettingRecord
    {
        $userId = \Craft::$app->user->getId();
        $settings = ExportSettingRecord::findOne(['userId' => $userId]);

        if (!$settings) {
            $settings = ExportSettingRecord::create($userId);
        }

        return $settings;
    }
}
