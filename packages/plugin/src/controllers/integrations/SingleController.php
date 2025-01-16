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

namespace Solspace\Freeform\controllers\integrations;

use craft\helpers\StringHelper;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\controllers\BaseController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Models\IntegrationModel;
use Solspace\Freeform\Resources\Bundles\IntegrationsSingletonBundle;
use yii\web\Response;

class SingleController extends BaseController
{
    public function actionIndex(?string $handle = null): Response
    {
        $this->view->registerAssetBundle(IntegrationsSingletonBundle::class);

        $integrationsService = Freeform::getInstance()->integrations;

        $types = $integrationsService->getAllServiceProviders(Type::TYPE_SINGLE);
        $integrations = $integrationsService->getAllIntegrations(Type::TYPE_SINGLE);
        $integrationsByClass = [];
        foreach ($integrations as $integration) {
            $this->getIntegrationsService()->decryptModelValues($integration);
            $integrationsByClass[$integration->class] = $integration;
        }

        return $this->renderTemplate(
            'freeform/settings/integrations/singleton/list',
            [
                'title' => 'Single',
                'types' => $types,
                'integrations' => $integrationsByClass,
                'handle' => $handle,
            ],
        );
    }

    public function actionSave(): Response
    {
        $this->requirePostRequest();

        $properties = \Craft::$app->request->post('properties', []);
        $integrationsService = Freeform::getInstance()->integrations;

        /** @var Type[] $types */
        $types = $integrationsService->getAllServiceProviders(Type::TYPE_SINGLE);
        $models = $integrationsService->getAllIntegrations(Type::TYPE_SINGLE);
        foreach ($models as $index => $model) {
            $models[$model->class] = $model;
            unset($models[$index]);
        }

        foreach ($types as $type) {
            $props = $properties[$type->class] ?? null;
            if (!$props) {
                continue;
            }

            $enabled = (bool) $props['enabled'] ?? false;
            unset($props['enabled']);

            $model = $models[$type->class] ?? null;
            if (!$model) {
                $model = new IntegrationModel();
                $model->class = $type->class;
                $model->type = Type::TYPE_SINGLE;
                $model->name = $type->name;
                $model->handle = StringHelper::toKebabCase($type->name);
            }

            $model->enabled = $enabled;
            $model->metadata = $props ?? [];

            $this->getIntegrationsService()->parsePostedModelData($model);

            $integration = $model->getIntegrationObject();

            $this->getIntegrationsService()->save($model, $integration, true);
        }

        $selectedIntegration = $this->request->post('selectedIntegration');

        return $this->redirect('freeform/settings/integrations/single/'.$selectedIntegration);
    }
}
