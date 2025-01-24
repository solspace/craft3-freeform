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

use craft\helpers\Assets;
use craft\web\Controller;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\HandleHelper;
use Solspace\Freeform\Models\FieldModel;
use Solspace\Freeform\Resources\Bundles\FieldEditorBundle;
use Solspace\Freeform\Resources\Bundles\FormIndexBundle;
use Solspace\Freeform\Services\FieldsService;
use Solspace\Freeform\Services\FilesService;
use yii\web\Response;

class FieldsController extends Controller
{
    public function actionIndex(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_ACCESS);

        $fieldsService = $this->getFieldsService();
        $fields = $fieldsService->getAllFields();
        usort($fields, function (FieldModel $a, FieldModel $b) {
            return $a->label <=> $b->label;
        });

        \Craft::$app->view->registerAssetBundle(FormIndexBundle::class);

        return $this->renderTemplate(
            'freeform/fields',
            [
                'fields' => $fields,
                'fieldTypes' => AbstractField::getFieldTypes(),
            ]
        );
    }

    public function actionCreate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);

        $model = FieldModel::create();

        return $this->renderEditForm($model, 'Create new field');
    }

    public function actionEdit(int $id): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);

        $model = $this->getFieldsService()->getFieldById($id);

        if (!$model) {
            throw new \HttpException(404, Freeform::t('Field with ID {id} not found', ['id' => $id]));
        }

        return $this->renderEditForm($model, $model->label);
    }

    public function actionDuplicate(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);
        $this->requirePostRequest();

        $id = \Craft::$app->request->post('id');
        $model = $this->getFieldsService()->getFieldById($id);

        if (!$model) {
            throw new FreeformException(
                Freeform::t('Field with ID {id} not found', ['id' => $id])
            );
        }

        $model->id = null;
        $oldHandle = $model->handle;

        if (preg_match('/^([a-zA-Z0-9]*[a-zA-Z]+)(\d+)$/', $oldHandle, $matches)) {
            [$string, $mainPart, $iterator] = $matches;

            $newHandle = $mainPart.((int) $iterator + 1);
        } else {
            $newHandle = $oldHandle.'1';
        }

        $model->handle = $newHandle;
        $this->getFieldsService()->save($model);

        if ($model->getErrors()) {
            $string = '';
            foreach ($model->getErrors() as $errors) {
                $string .= implode(', ', $errors);
            }

            \Craft::$app->session->setError($string);
        }

        return $this->redirect('freeform/fields');
    }

    public function actionSave(): Response
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);

        $post = \Craft::$app->request->post();

        $fieldId = $post['fieldId'] ?? null;
        $field = $this->getNewOrExistingField($fieldId);

        if ($field->id && isset($post['type'])) {
            unset($post['type']);
        }

        $field->setAttributes($post);
        $field->handle = HandleHelper::generateHandle($post['handle']);
        $field->required = (bool) ($post['required'] ?? false);

        $fieldHasOptions = \in_array(
            $field->type,
            [
                FieldInterface::TYPE_RADIO_GROUP,
                FieldInterface::TYPE_CHECKBOX_GROUP,
                FieldInterface::TYPE_MULTIPLE_SELECT,
                FieldInterface::TYPE_SELECT,
                FieldInterface::TYPE_DYNAMIC_RECIPIENTS,
            ],
            true
        );

        if (isset($post['types'][$field->type])) {
            $fieldSpecificPost = $post['types'][$field->type];
            $field->addMetaProperties($fieldSpecificPost);

            $hasValues = isset($fieldSpecificPost['values']) && \is_array($fieldSpecificPost['values']);
            $forceLabelOnValue = isset($fieldSpecificPost['customValues']) && '1' !== $fieldSpecificPost['customValues'];

            if ($fieldHasOptions && $hasValues) {
                $field->setPostValues($fieldSpecificPost, $forceLabelOnValue);
            } else {
                $field->setMetaProperty('values', null);
            }
        }

        if ($this->getFieldsService()->save($field)) {
            // Return JSON response if the request is an AJAX request
            if (\Craft::$app->request->isAjax) {
                return $this->asJson(['success' => true]);
            }

            \Craft::$app->session->setNotice(Freeform::t('Field saved'));
            \Craft::$app->session->setFlash('Field saved', true);

            return $this->redirectToPostedUrl($field);
        }

        // Return JSON response if the request is an AJAX request
        if (\Craft::$app->request->isAjax) {
            return $this->asJson(['success' => false]);
        }

        \Craft::$app->session->setError(Freeform::t('Field not saved'));

        return $this->renderEditForm($field, $field->label);
    }

    public function actionDelete(): Response
    {
        $this->requirePostRequest();
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);

        $fieldId = \Craft::$app->request->post('id');

        return $this->asJson([
            'success' => $this->getFieldsService()->deleteById((int) $fieldId),
        ]);
    }

    private function renderEditForm(FieldModel $model, string $title): Response
    {
        $this->view->registerAssetBundle(FieldEditorBundle::class);

        $fileKinds = Assets::getAllowedFileKinds();
        $fileKindOptions = [];
        foreach ($fileKinds as $key => $item) {
            $fileKindOptions[] = [
                'label' => $item['label'],
                'value' => $key,
            ];
        }

        $fieldTypes = $this->getFieldsService()->getEditableFieldTypes();

        $variables = [
            'field' => $model,
            'title' => $title,
            'fieldTypes' => $fieldTypes,
            'fileKinds' => $fileKinds,
            'fileKindOptions' => $fileKindOptions,
            'assetSources' => $this->getFilesService()->getAssetSourceList(),
            'continueEditingUrl' => 'freeform/fields/{id}',
        ];

        return $this->renderTemplate('freeform/fields/edit', $variables);
    }

    private function getFieldsService(): FieldsService
    {
        return Freeform::getInstance()->fields;
    }

    private function getFilesService(): FilesService
    {
        return Freeform::getInstance()->files;
    }

    private function getNewOrExistingField(?int $fieldId): FieldModel
    {
        if ($fieldId) {
            $field = $this->getFieldsService()->getFieldById($fieldId);

            if (!$field) {
                throw new FreeformException(Freeform::t('Field with ID {id} not found', ['id' => $fieldId]));
            }
        } else {
            $field = FieldModel::create();
        }

        return $field;
    }
}
