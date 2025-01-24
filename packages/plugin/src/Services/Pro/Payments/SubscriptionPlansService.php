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

namespace Solspace\Freeform\Services\Pro\Payments;

use Solspace\Freeform\Library\Pro\Payments\Traits\ModelServiceTrait;
use Solspace\Freeform\Models\Pro\Payments\SubscriptionPlanModel;
use Solspace\Freeform\Records\Pro\Payments\SubscriptionPlanRecord;
use yii\db\Query;

class SubscriptionPlansService
{
    use ModelServiceTrait;

    /**
     * Returns payment plans for integration.
     *
     * @return SubscriptionPlanModel[]
     */
    public function getByIntegrationId(int $integrationId): array
    {
        $data = $this->getQuery()->where(['integrationId' => $integrationId])->all();

        if (!$data) {
            $data = [];
            // TODO: query integration on miss?
        }

        return array_map([$this, 'createModel'], $data);
    }

    /**
     * Returns payment plan with a resource id on specific integration.
     *
     * @return null|SubscriptionPlanModel
     */
    public function getByResourceId(string $resourceId, int $integrationId)
    {
        $data = $this->getQuery()->where([
            'integrationId' => $integrationId,
            'resourceId' => $resourceId,
        ])->one();

        if (!$data) {
            return null;
        }

        return $this->createModel($data);
    }

    /**
     * Returns payment plan for id.
     *
     * @return null|SubscriptionPlanModel
     */
    public function getById(int $id)
    {
        $data = $this->getQuery()->where(['id' => $id])->one();
        if (!$data) {
            return null;
        }

        return $this->createModel($data);
    }

    /**
     * Saves subscription plan.
     */
    public function save(SubscriptionPlanModel $model): bool
    {
        $isNew = !$model->id;
        if (!$isNew) {
            $record = SubscriptionPlanRecord::findOne(['id' => $model->id]);
        } else {
            $record = new SubscriptionPlanRecord();

            $record->integrationId = $model->integrationId;
            $record->resourceId = $model->resourceId;
        }

        $record->name = $model->name;

        if ($this->validateAndSave($record, $model)) {
            $model->id = $record->id;

            return true;
        }

        return false;
    }

    /**
     * Refreshes list of payment plans for integration.
     *
     * @param SubscriptionPlanModel[] $plans
     */
    public function updateIntegrationPlans(int $integrationId, array $plans)
    {
        $oldPlans = $this->getByIntegrationId($integrationId);

        $oldPlansByResourceId = [];
        foreach ($oldPlans as $plan) {
            $oldPlansByResourceId[strtolower($plan->resourceId)] = $plan;
        }

        foreach ($plans as $plan) {
            $resourceId = strtolower($plan->resourceId);
            if (isset($oldPlansByResourceId[$resourceId])) {
                $oldPlan = $oldPlansByResourceId[$resourceId];
                $plan->id = $oldPlan->id;
                unset($oldPlansByResourceId[$resourceId]);
            }
            $this->save($plan);
        }

        foreach ($oldPlansByResourceId as $plan) {
            $this->delete($plan->id);
        }
    }

    /**
     * Deletes subscription plan.
     */
    public function delete(int $id)
    {
        SubscriptionPlanRecord::deleteAll(['id' => $id]);
    }

    protected function getQuery(): Query
    {
        return SubscriptionPlanRecord::find();
    }

    protected function createModel($data): SubscriptionPlanModel
    {
        if ($data instanceof SubscriptionPlanRecord) {
            $data = $data->toArray();
        }

        return new SubscriptionPlanModel($data);
    }
}
