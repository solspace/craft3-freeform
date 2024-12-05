<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Records;

use craft\db\ActiveRecord;

/**
 * Class Freeform_FormRecord.
 *
 * @property int       $id
 * @property string    $type
 * @property string    $metadata
 * @property string    $name
 * @property string    $handle
 * @property int       $order
 * @property int       $spamBlockCount
 * @property int       $createdByUserId
 * @property int       $updatedByUserId
 * @property \DateTime $dateArchived
 */
class FormRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms}}';
    public const TABLE_STD = 'freeform_forms';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * Factory Method.
     */
    public static function create(): self
    {
        $form = new self();
        $form->spamBlockCount = 0;

        return $form;
    }

    public function rules(): array
    {
        return [
            [['handle'], 'unique'],
            [['name', 'handle'], 'required'],
        ];
    }
}
