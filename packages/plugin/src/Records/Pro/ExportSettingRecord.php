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

namespace Solspace\Freeform\Records\Pro;

use craft\db\ActiveRecord;

/**
 * Class Freeform_ExportSettingRecord.
 *
 * @property int   $id
 * @property int   $userId
 * @property array $setting
 */
class ExportSettingRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_export_settings}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * @param int $userId
     *
     * @return $this
     */
    public static function create($userId): self
    {
        $record = new self();

        $record->userId = $userId;
        $record->setting = [];

        return $record;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['userId'], 'required'],
        ];
    }
}
