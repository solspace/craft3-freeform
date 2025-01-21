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
 * @property string $category
 * @property string $handle
 * @property string $label
 * @property string $type
 * @property bool   $required
 * @property string $options
 */
class EmailMarketingFieldRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_email_marketing_fields}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @return ActiveQuery|EmailMarketingListRecord
     */
    public function getMailingList(): ActiveQuery
    {
        return $this->hasOne(EmailMarketingListRecord::class, ['mailingListId' => 'id']);
    }
}
