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
 * @property int       $formId
 * @property int       $layoutId
 * @property string    $label
 * @property int       $order
 * @property string    $metadata
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FormPageRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms_pages}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [
            [['formId', 'layoutId'], 'required'],
        ];
    }
}
