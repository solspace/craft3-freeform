<?php

namespace Solspace\Freeform\controllers\api\forms;

use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\fields\Matrix;
use craft\models\FieldLayout;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\FieldTypes\FormFieldType;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use yii\db\Expression;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ElementsController extends BaseApiController
{
    public function actionGet(int $id): Response
    {
        $form = $this->getFormsService()->getFormById($id);
        if (!$form) {
            throw new NotFoundHttpException("Form with ID {$id} not found");
        }

        $site = SitesHelper::getPostedSite();
        if (!$site) {
            $site = \Craft::$app->sites->getCurrentSite();
        }

        $driver = \Craft::$app->getDb()->getDriverName();
        $service = \Craft::$app->getFields();

        $fieldUids = [];
        $layouts = $service->getAllLayouts();
        foreach ($layouts as $layout) {
            $this->_getCustomFieldElements($layout, $fieldUids);
        }

        $conditions = ['or'];
        foreach ($fieldUids as $uuid) {
            $conditions[] = new Expression(
                match ($driver) {
                    'mysql' => "JSON_UNQUOTE(JSON_EXTRACT([[es.content]], '$.\"{$uuid}\"')) = :formId",
                    'pgsql' => '[[es.content]]->>:uuid = :formId',
                    default => throw new \RuntimeException("Unsupported driver: {$driver}"),
                },
                [':uuid' => $uuid, ':formId' => $id]
            );
        }

        $elementIds = (new Query())
            ->select(['[[es.elementId]]'])
            ->from(['es' => Table::ELEMENTS_SITES])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[es.elementId]]')
            ->where($conditions)
            ->andWhere([
                '[[e.draftId]]' => null,
                '[[e.revisionId]]' => null,
                '[[e.dateDeleted]]' => null,
                '[[es.enabled]]' => true,
                '[[es.siteId]]' => $site->id,
            ])
            ->distinct('[[es.elementId]]')
            ->column()
        ;

        $elements = [];
        foreach ($elementIds as $elementId) {
            $elements[] = \Craft::$app->elements->getElementById($elementId, siteId: $site->id);
        }

        $elements = array_map(
            fn (ElementInterface $element) => [
                'id' => $element->id,
                'title' => $this->_getElementTitle($element),
                'type' => $element::displayName(),
                'status' => ucfirst($element->getStatus()),
                'url' => $element->getCpEditUrl(),
            ],
            $elements,
        );

        return $this->asJson($elements);
    }

    private function _getCustomFieldElements(FieldLayout $layout, array &$fieldUids = []): void
    {
        $customFields = $layout->getCustomFieldElements();
        foreach ($customFields as $customField) {
            $field = $customField->getField();
            if ($field instanceof Matrix) {
                foreach ($field->getEntryTypes() as $entryType) {
                    $this->_getCustomFieldElements($entryType->getFieldLayout(), $fieldUids);
                }
            } elseif ($field instanceof FormFieldType) {
                $fieldUids[] = $customField->uid;
            }
        }
    }

    private function _getElementTitle(ElementInterface $element): string
    {
        if ($element instanceof User) {
            return $element->fullName ?: $element->username;
        }

        return $element->title ?: 'Entry '.$element->id;
    }
}
