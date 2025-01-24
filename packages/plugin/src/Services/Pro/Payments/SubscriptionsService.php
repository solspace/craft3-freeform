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
use Solspace\Freeform\Models\Pro\Payments\SubscriptionModel;
use Solspace\Freeform\Records\Pro\Payments\SubscriptionRecord;
use yii\db\Query;

class SubscriptionsService
{
    use ModelServiceTrait;

    /**
     * Finds a subscription by submission id.
     *
     * @return null|SubscriptionModel
     */
    public function getBySubmissionId(int $submissionId)
    {
        /** @var SubscriptionRecord $data */
        $data = $this->getQuery()->where(['submissionId' => $submissionId])->one();

        if (!$data) {
            return null;
        }

        return new SubscriptionModel($data->toArray());
    }

    /**
     * Finds a subscription by id.
     *
     * @return null|SubscriptionModel
     */
    public function getById(int $id)
    {
        /** @var SubscriptionRecord $data */
        $data = $this->getQuery()->where(['id' => $id])->one();

        if (!$data) {
            return null;
        }

        return new SubscriptionModel($data->toArray());
    }

    /**
     * Saves subscription model.
     */
    public function save(SubscriptionModel $model): bool
    {
        $isNew = !$model->id;
        if (!$isNew) {
            $record = SubscriptionRecord::findOne(['id' => $model->id]);
        } else {
            $record = new SubscriptionRecord();

            $record->integrationId = $model->integrationId;
            $record->submissionId = $model->submissionId;
            $record->resourceId = $model->resourceId;
            $record->planId = $model->planId;
        }

        $record->amount = $model->amount;
        $record->currency = $model->currency;
        $record->interval = $model->interval;
        $record->intervalCount = $model->intervalCount;
        $record->last4 = $model->last4;
        $record->status = $model->status;
        $record->metadata = $model->metadata;
        $record->errorCode = $model->errorCode;
        $record->errorMessage = $model->errorMessage;

        return $this->validateAndSave($record, $model);
    }

    public function updateSubscriptionStatus(int $submissionId, string $status)
    {
        $subscription = $this->getBySubmissionId($submissionId);
        $subscription->status = $status;
        $this->save($subscription);
    }

    protected function getQuery(): Query
    {
        return SubscriptionRecord::find();
    }
}
