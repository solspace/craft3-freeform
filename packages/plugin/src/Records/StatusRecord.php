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
 * @property int    $id
 * @property string $name
 * @property string $handle
 * @property string $color
 * @property int    $isDefault
 * @property int    $sortOrder
 */
class StatusRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_statuses}}';
    public const TABLE_STD = 'freeform_statuses';

    public static function getAllowedColors(): array
    {
        return [
            'green',
            'blue',
            'yellow',
            'orange',
            'red',
            'pink',
            'purple',
            'turquoise',
            'light',
            'grey',
            'black',
        ];
    }

    public static function create(): self
    {
        $colors = self::getAllowedColors();
        shuffle($colors);
        $randomColor = reset($colors);

        $field = new static();
        $field->color = $randomColor;

        return $field;
    }

    public static function tableName(): string
    {
        return self::TABLE;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['handle'], 'unique'],
            [['name', 'handle'], 'required'],
        ];
    }
}
