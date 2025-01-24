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
 * Class Freeform_NotificationRecord.
 *
 * @property int    $id
 * @property string $contextKey
 * @property string $sessionId
 * @property int    $formId
 * @property string $propertyBag
 * @property string $attributeBag
 */
class SessionContextRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_session_context}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
