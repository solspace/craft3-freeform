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
 * Class IntegrationRecord.
 *
 * @property int    $id
 * @property bool   $enabled
 * @property string $name
 * @property string $handle
 * @property string $type
 * @property string $class
 * @property string $metadata
 */
class IntegrationRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_integrations}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [
            [['handle'], 'unique'],
            [['name', 'handle'], 'required'],
        ];
    }
}
