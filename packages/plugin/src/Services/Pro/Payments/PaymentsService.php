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

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Payments\PaymentHandlerInterface;
use Solspace\Freeform\Library\Payments\PaymentInterface;
use Solspace\Freeform\Library\Pro\Payments\Traits\ModelServiceTrait;
use Solspace\Freeform\Models\Pro\Payments\PaymentModel;
use Solspace\Freeform\Models\Pro\Payments\SubscriptionModel;
use Solspace\Freeform\Records\Pro\Payments\PaymentRecord;
use yii\db\Query;

class PaymentsService implements PaymentHandlerInterface
{
    use ModelServiceTrait;

    /**
     * Returns payment for submission, only first payment is returned for subscriptions.
     *
     * @return null|PaymentInterface
     */
    public function getBySubmissionId(int $submissionId)
    {
        $data = $this->getQuery()->where(['submissionId' => $submissionId])->all();
        if (!$data) {
            return null;
        }

        // for multiple subscription payments we get only first one
        $data = $data[0]->toArray();

        if (!$data) {
            $data = [];
        }

        return $this->createModel($data);
    }

    /**
     * Finds a payment with a matching resource id for specific integration.
     *
     * @return null|PaymentModel
     */
    public function getByResourceId(string $resourceId, int $integrationId)
    {
        $data = $this->getQuery()->where([
            'resourceId' => $resourceId,
            'integrationId' => $integrationId,
        ])->one();

        if (!$data) {
            return null;
        }

        return $this->createModel($data);
    }

    /**
     * Saves payment.
     *
     * @param PaymentInterface|PaymentModel $model
     */
    public function save(PaymentInterface $model): bool
    {
        $isNew = !$model->id;
        if (!$isNew) {
            $record = PaymentRecord::findOne(['id' => $model->id]);
        } else {
            $record = new PaymentRecord();

            $record->integrationId = $model->integrationId;
            $record->resourceId = $model->resourceId;
            $record->submissionId = $model->submissionId;
        }

        $record->subscriptionId = $model->subscriptionId;
        $record->resourceId = $model->resourceId;
        $record->amount = $model->amount;
        $record->currency = $model->currency;
        $record->last4 = $model->last4;
        $record->status = $model->status;
        $record->metadata = $model->metadata;
        $record->errorCode = $model->errorCode;
        $record->errorMessage = $model->errorMessage;

        return $this->validateAndSave($record, $model);
    }

    /**
     * Returns Subscription or Payment model depending on payment type.
     *
     * @return null|PaymentModel|SubscriptionModel
     */
    public function getPaymentDetails(int $submissionId, Form $form = null)
    {
        if (null === $form) {
            $submission = Freeform::getInstance()->submissions->getSubmissionById($submissionId);
            $form = $submission->getForm();
        }

        $paymentProperties = $form->getPaymentProperties();
        $integrationId = $paymentProperties->getIntegrationId();
        if (!$integrationId) {
            return null;
        }

        $integration = Freeform::getInstance()->paymentGateways->getIntegrationObjectById($integrationId);

        return $integration->getPaymentDetails($submissionId);
    }

    public function updatePaymentStatus(int $submissionId, string $status)
    {
        $payment = $this->getBySubmissionId($submissionId);
        $payment->status = $status;
        $this->save($payment);
    }

    protected function getQuery(): Query
    {
        return PaymentRecord::find();
    }

    /**
     * Creates model from attributes.
     */
    protected function createModel(array $data): PaymentModel
    {
        return new PaymentModel($data);
    }
}
