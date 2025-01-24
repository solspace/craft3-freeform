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

namespace Solspace\Freeform\Records\Pro\Payments;

use craft\db\ActiveRecord;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Records\IntegrationRecord;
use yii\db\ActiveQuery;

/**
 * @property string $id
 * @property int    $submissionId
 * @property int    $integrationId
 * @property int    $subscriptionId
 * @property string $resourceId
 * @property float  $amount
 * @property string $currency
 * @property int    $last4
 * @property string $status
 * @property string $metadata
 * @property string $errorCode
 * @property string $errorMessage
 */
class PaymentRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_payments_payments}}';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @return ActiveQuery|IntegrationRecord
     */
    public function getIntegration(): ActiveQuery
    {
        return $this->hasOne(IntegrationRecord::class, ['integrationId' => 'id']);
    }

    /**
     * @return ActiveQuery|Submission
     */
    public function getSubmission(): ActiveQuery
    {
        return $this->hasOne(Submission::class, ['submissionId' => 'id']);
    }

    /**
     * @return ActiveQuery|SubscriptionRecord
     */
    public function getSubscription(): ActiveQuery
    {
        return $this->hasOne(SubscriptionRecord::class, ['subscriptionId' => 'id']);
    }
}
