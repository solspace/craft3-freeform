<?php

namespace Solspace\Freeform\controllers\api;

use Solspace\Freeform\Bundles\Persistence\Duplication\FormDuplicator;
use Solspace\Freeform\Bundles\Transformers\Builder\Form\FormTransformer;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Records\FormRecord;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FormsController extends BaseApiController
{
    public const EVENT_UPSERT_FORM = 'upsert-form';
    public const EVENT_CREATE_FORM = 'create-form';
    public const EVENT_UPDATE_FORM = 'update-form';

    public const EVENT_AFTER_SAVE_FORM = 'after-save-form';

    public function __construct(
        $id,
        $module,
        $config,
        private FormTransformer $formTransformer,
        private FormDuplicator $formDuplicator,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionClone(int $id): Response
    {
        $this->requireFormPermission($id);

        if (Freeform::getInstance()->edition()->isBelow(Freeform::EDITION_LITE)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        if (!$this->formDuplicator->clone($id)) {
            throw new FreeformException('Could not duplicate form');
        }

        $this->response->statusCode = 204;
        $this->response->format = Response::FORMAT_RAW;
        $this->response->content = '';

        return $this->response;
    }

    public function actionArchive(int $id): Response
    {
        $freeform = Freeform::getInstance();
        $this->requireFormPermission($id);

        if ($freeform->edition()->isBelow(Freeform::EDITION_LITE)) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }

        $form = $this->getFormsService()->getFormById($id);
        if (!$form) {
            throw new NotFoundHttpException("Form with ID {$id} not found");
        }

        $dateArchived = ($form->getDateArchived()) ? null : (new \DateTime())->format('Y-m-d');

        \Craft::$app->db->createCommand()
            ->update(
                FormRecord::TABLE,
                ['dateArchived' => $dateArchived],
                ['id' => $id]
            )
            ->execute()
        ;

        if ($freeform->isPro()) {
            $freeform->formGroups->deleteById($id);
        }

        $this->response->statusCode = 204;
        $this->response->format = Response::FORMAT_RAW;
        $this->response->content = '';

        return $this->response;
    }

    public function actionSort(): Response
    {
        $this->requireFormPermission();

        $data = json_decode($this->request->getRawBody());
        $orderedFormIds = $data->orderedFormIds ?? [];

        $order = 0;
        foreach ($orderedFormIds as $id) {
            \Craft::$app->db->createCommand()
                ->update(
                    FormRecord::TABLE,
                    ['order' => $order++],
                    ['id' => $id]
                )
                ->execute()
            ;
        }

        $this->response->statusCode = 204;
        $this->response->format = Response::FORMAT_RAW;
        $this->response->content = '';

        return $this->response;
    }

    public function actionDelete(): Response
    {
        $this->requireFormPermission(permission: Freeform::PERMISSION_FORMS_DELETE);

        $this->requirePostRequest();
        $id = (int) \Craft::$app->request->post('id');
        if (!$id) {
            throw new NotFoundHttpException('No form ID provided');
        }

        $this->requireFormPermission($id);
        $this->requireFormPermission($id, Freeform::PERMISSION_FORMS_DELETE);

        $this->getFormsService()->deleteById($id);

        return $this->asEmptyResponse(204);
    }

    protected function get(): array
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FORMS_ACCESS);

        $sites = SitesHelper::getCurrentCpPageSiteHandle();

        return $this->formTransformer->transformList(
            array_values(
                $this->getFormsService()->getAllForms(sites: $sites)
            )
        );
    }

    protected function getOne($id): null|array|object
    {
        $this->requireFormPermission($id, Freeform::PERMISSION_FORMS_ACCESS);

        $form = $this->getFormsService()->getFormById($id);
        if (!$form) {
            throw new NotFoundHttpException("Form with ID {$id} not found");
        }

        return $this->formTransformer->transform($form);
    }

    protected function post(null|int|string $id = null): null|array|object
    {
        $this->requireFormPermission($id);

        $data = json_decode($this->request->getRawBody(), false);

        $event = new PersistFormEvent($data, $id);
        $this->trigger(self::EVENT_UPDATE_FORM, $event);
        $this->trigger(self::EVENT_UPSERT_FORM, $event);
        $this->trigger(self::EVENT_AFTER_SAVE_FORM, $event);

        $this->response->statusCode = $event->getStatus() ?? 204;

        return $event->getResponseData();
    }

    private function requireFormPermission(?int $id = null, string $permission = Freeform::PERMISSION_FORMS_MANAGE): void
    {
        $nestedPermission = PermissionHelper::prepareNestedPermission($permission, $id ?? 0);

        $canManageAll = PermissionHelper::checkPermission($permission);
        $canManageCurrent = PermissionHelper::checkPermission($nestedPermission);

        if (!$canManageAll && !$canManageCurrent) {
            throw new ForbiddenHttpException('User is not permitted to perform this action');
        }
    }
}
