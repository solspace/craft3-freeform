<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\controllers\api\forms;

use Solspace\Freeform\Bundles\Notifications\Providers\NotificationsProvider;
use Solspace\Freeform\controllers\BaseApiController;
use Solspace\Freeform\Library\Serialization\Normalizers\IdentificationNormalizer;
use Symfony\Component\Serializer\Serializer;
use yii\web\Response;

class NotificationsController extends BaseApiController
{
    public function __construct(
        $id,
        $module,
        $config,
        private NotificationsProvider $formNotificationsProvider,
        private Serializer $serializer,
    ) {
        parent::__construct($id, $module, $config ?? []);
    }

    public function actionGet(int $formId): Response
    {
        $form = $this->getFormsService()->getFormById($formId);
        if (!$form) {
            return $this->asJson([]);
        }

        $notifications = $this->formNotificationsProvider->getByForm($form);

        $serialized = $this->serializer->serialize($notifications, 'json', [
            IdentificationNormalizer::NORMALIZE_TO_IDENTIFICATORS => true,
        ]);

        $this->response->format = Response::FORMAT_JSON;
        $this->response->content = $serialized;

        return $this->response;
    }
}
