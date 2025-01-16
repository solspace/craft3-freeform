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
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Records\IntegrationRecord;
use yii\db\ActiveQuery;

/**
 * @property string $id
 * @property int    $integrationId
 * @property int    $fieldId
 * @property int    $submissionId
 * @property string $resourceId
 * @property string $type
 * @property float  $amount
 * @property string $currency
 * @property string $status
 * @property string $link
 * @property string $metadata
 */
class PaymentRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_payments}}';

    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';

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

    public function getPaymentMethod(): ?\stdClass
    {
        $metadata = JsonHelper::decode($this->metadata);

        if (!isset($metadata->type)) {
            return null;
        }

        return $metadata;
    }
}
