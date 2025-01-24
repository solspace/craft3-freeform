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
use Solspace\Freeform\Library\DataObjects\SpamReason;

/**
 * Class SpamReasonRecord.
 *
 * @property int    $id
 * @property int    $submissionId
 * @property string $reasonType
 * @property string $reasonMessage
 */
class SpamReasonRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_spam_reason}}';
    public const TABLE_STD = 'freeform_spam_reason';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getSpamReasonObject(): SpamReason
    {
        return new SpamReason($this->reasonType, $this->reasonMessage);
    }
}
