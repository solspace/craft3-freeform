<?php

namespace Solspace\Freeform\controllers\export;

use craft\db\Query;
use craft\db\Table;
use Solspace\Freeform\Controllers\BaseController;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\EncryptionHelper;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Records\Pro\ExportSettingRecord;
use Solspace\Freeform\Records\StatusRecord;
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

        // TODO: reimplement with payments

        // $paymentProperties = $form->getPaymentProperties();
        // $hasPaymentSingles = PaymentProperties::PAYMENT_TYPE_SINGLE === $paymentProperties->getPaymentType();
        // $hasPaymentSubscriptions = !$hasPaymentSingles && null !== $paymentProperties->getPaymentType();

        $settings->setting = $exportFields;
        $settings->save();

        $searchableFields = [];
        foreach ($fieldData as $fieldId => $data) {
            $isChecked = $data['checked'];

            if (!(bool) $isChecked) {
                continue;
            }

            if (is_numeric($fieldId)) {
                $field = $form->get($fieldId);
                if ($field) {
                    $fieldName = Submission::getFieldColumnName($field);
                    $fieldHandle = $field->getHandle();

                    $searchableFields[] = "[[sc.{$fieldName}]] as {$fieldHandle}";
                }
            } else {
                $fieldName = $fieldId;
                $fieldName = match ($fieldName) {
                    'title' => $isCraft5 ? 'es.[[title]]' : 'c.[['.$fieldName.']]',
                    'status' => 'stat.[[name]] as status',
                    'cc_status' => 'p.[[status]] as cc_status',
                    'cc_amount' => 'p.[[amount]] as cc_amount',
                    'cc_currency' => 'p.[[currency]] as cc_currency',
                    'cc_card' => 'p.[[last4]] as cc_card',
                    default => 's.[['.$fieldName.']]',
                };

                $searchableFields[] = $fieldName;
            }
        }

        $query = (new Query())
            ->select($searchableFields)
            ->from(Submission::TABLE.' s')
            ->innerJoin(Submission::getContentTableName($form).' sc', 'sc.[[id]] = s.[[id]]')
            ->innerJoin(StatusRecord::TABLE.' stat', 'stat.[[id]] = s.[[statusId]]')
            ->where(['s.[[formId]]' => $form->getId()])
            ->andWhere(['s.[[isSpam]]' => $isSpam])
        ;

        if ($isCraft5) {
            $query->innerJoin('{{%elements_sites}} es', 'es.[[elementId]] = s.[[id]]');
        } else {
            $query->innerJoin('{{%content}} c', 'c.[[elementId]] = s.[[id]]');
        }

        // TODO: reimplement with payments

        // if ($hasPaymentSingles) {
        //     $query->leftJoin('{{%freeform_payments_payments}} p', 'p.[[submissionId]] = s.[[id]]');
        // } elseif ($hasPaymentSubscriptions) {
        //     $query->leftJoin('{{%freeform_payments_subscriptions}} p', 'p.[[submissionId]] = s.[[id]]');
        // }

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
            $query->innerJoin(
                $elements.' e',
                'e.[[id]] = s.[[id]] AND e.[[dateDeleted]] IS NULL'
            );
        }

        $data = $query->all();

        $key = EncryptionHelper::getKey($form->getUid());
        $data = EncryptionHelper::decryptExportData($key, $data);

        $exportProfilesService = $this->getExportProfileService();

        $exporter = $exportProfilesService->createExporter($exportType, $form, $data);

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
