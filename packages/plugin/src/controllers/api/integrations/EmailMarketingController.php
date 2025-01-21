<?php

namespace Solspace\Freeform\controllers\api\integrations;

use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\EmailMarketingIntegrationInterface;
use Solspace\Freeform\Services\Integrations\EmailMarketingService;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class EmailMarketingController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private EmailMarketingService $emailMarketingService,
    ) {
        parent::__construct($id, $module, $config);
    }

    public function actionLists(): Response
    {
        $id = $this->request->get('id');
        $refresh = $this->request->get('refresh', false);

        $integration = Freeform::getInstance()->integrations->getIntegrationObjectById($id);
        if (!$integration instanceof EmailMarketingIntegrationInterface) {
            throw new NotFoundHttpException('Integration not found');
        }

        $lists = $this->emailMarketingService->getLists($integration, $refresh);

        $options = new OptionCollection();
        foreach ($lists as $list) {
            $options->add($list->getId(), $list->getName());
        }

        return $this->asSerializedJson($options);
    }

    public function actionFields(string $category): Response
    {
        $id = $this->request->get('id');
        $refresh = $this->request->get('refresh', false);
        if (!$id) {
            throw new NotFoundHttpException('Integration not found');
        }

        $integration = Freeform::getInstance()->integrations->getIntegrationObjectById($id);
        if (!$integration instanceof EmailMarketingIntegrationInterface) {
            throw new NotFoundHttpException('Integration not found');
        }

        $listId = $this->request->get('mailingListId');
        $list = $this->getEmailMarketingService()->getListObjectById((int) $listId);
        if (!$list) {
            return $this->asSerializedJson([]);
        }

        $fields = $this->getEmailMarketingService()->getFields($list, $integration, $category, $refresh);

        $payload = [];
        foreach ($fields as $field) {
            $payload[] = [
                'id' => $field->getHandle(),
                'label' => $field->getLabel(),
                'required' => $field->isRequired(),
                'type' => $field->getType(),
                'options' => $field->getOptions(),
            ];
        }

        $serialized = $this->getSerializer()->serialize($payload, 'json');

        $this->response->format = Response::FORMAT_JSON;
        $this->response->content = $serialized;

        return $this->response;
    }
}
