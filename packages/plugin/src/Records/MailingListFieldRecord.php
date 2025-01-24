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
use yii\db\ActiveQuery;

/**
 * @property string $mailingListId
 * @property string $handle
 * @property string $label
 * @property string $type
 * @property bool   $required
 */
class MailingListFieldRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_mailing_list_fields}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @return ActiveQuery|MailingListRecord
     */
    public function getMailingList(): ActiveQuery
    {
        return $this->hasOne(MailingListRecord::class, ['mailingListId' => 'id']);
    }
}
