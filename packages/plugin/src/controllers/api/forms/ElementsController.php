<?php

namespace Solspace\Freeform\controllers\api\forms;

use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\Table;
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
            $customFields = $layout->getCustomFieldElements();
            foreach ($customFields as $customField) {
                if ($customField->getField() instanceof FormFieldType) {
                    $fieldUids[] = $customField->uid;
                }
            }
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
                'title' => $element->title,
                'type' => $element::displayName(),
                'status' => $element->getStatus(),
                'url' => $element->getCpEditUrl(),
            ],
            $elements,
        );

        return $this->asJson($elements);
    }
}
