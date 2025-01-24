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

namespace Solspace\Freeform\Records;

use craft\db\ActiveRecord;

/**
 * Class IntegrationsQueueRecord.
 *
 * @property int    $id
 * @property int    $submissionId
 * @property string $fieldHash
 * @property string $integrationType
 * @property string $status
 * @property string $fieldValuesJson
 */
class IntegrationsQueueRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_integrations_queue}}';

    public const STATUS_ENQUEUED = 'enqueued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE = 'done';
    public const STATUS_FAILED = 'failed';
    public const STATUS_HALTED = 'halted';

    public const INTEGRATION_TYPE_MAILING_LIST = 'mailing_list';
    public const INTEGRATION_TYPE_CRM = 'crm';
    public const INTEGRATION_TYPE_NOTIFICATION = 'notification';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
