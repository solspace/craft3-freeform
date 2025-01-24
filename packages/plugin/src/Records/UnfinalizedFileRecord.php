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
use craft\records\Asset;
use yii\db\ActiveQuery;

/**
 * Class Freeform_FieldRecord.
 *
 * @property int    $id
 * @property int    $assetId
 * @property string $formToken
 * @property string $fieldHandle
 */
class UnfinalizedFileRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_unfinalized_files}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @return ActiveQuery|Asset
     */
    public function getAsset(): ActiveQuery
    {
        return $this->hasOne(Asset::class, ['assetId' => 'id']);
    }
}
