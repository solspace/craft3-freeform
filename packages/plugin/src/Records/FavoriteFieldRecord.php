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
 * @property int       $id
 * @property int       $userId
 * @property string    $label
 * @property string    $type
 * @property string    $metadata
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FavoriteFieldRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_favorite_fields}}';

    /**
     * Returns the name of the associated database table.
     */
    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [
            [['label'], 'unique'],
        ];
    }
}
