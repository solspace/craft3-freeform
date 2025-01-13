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

namespace Solspace\Freeform\Records\Form;

use craft\db\ActiveRecord;

/**
 * @property int       $id
 * @property string    $class
 * @property int       $formId
 * @property bool      $enabled
 * @property string    $metadata
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FormNotificationRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms_notifications}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [
            [['formId', 'class', 'uid'], 'required'],
        ];
    }

    public function safeAttributes(): array
    {
        return [
            'class',
            'formId',
            'enabled',
            'metadata',
        ];
    }
}
