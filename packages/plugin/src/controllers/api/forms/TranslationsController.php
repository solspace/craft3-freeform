<?php

namespace Solspace\Freeform\controllers\api;

use craft\db\Query;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Records\FormTranslationRecord;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TranslationsController extends BaseApiController
{
    public function actionGet(int $formId): Response
    {
        $form = $this->getFormsService()->getFormById($formId);
        if (!$form) {
            throw new NotFoundHttpException("Form with ID {$formId} not found");
        }

        $translations = (new Query())
            ->select(['siteId', 'translations'])
            ->from(FormTranslationRecord::tableName())
            ->where(['formId' => $form->getId()])
            ->indexBy('siteId')
            ->all()
        ;

        return $this->asJson($translations);
    }
}
