<?php

namespace Solspace\Freeform\Records;

use craft\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $formId
 * @property int    $siteId
 * @property string $translations
 */
class FormTranslationRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%freeform_forms_translations}}';
    }

    public function rules(): array
    {
        return [
            [['formId', 'siteId', 'translations'], 'required'],
            [['formId', 'siteId'], 'integer'],
            [['formId', 'siteId'], 'unique', 'targetAttribute' => ['formId', 'siteId']],
        ];
    }
}
