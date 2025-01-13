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
use yii\db\ActiveQuery;

/**
 * @property int       $id
 * @property int       $formId
 * @property int       $rowId
 * @property int       $order
 * @property string    $type
 * @property string    $metadata
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FormFieldRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms_fields}}';

    public ?string $handle = null;

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function getRow(): ActiveQuery
    {
        return $this->hasOne(FormRowRecord::class, ['id' => 'rowId']);
    }

    public function rules(): array
    {
        return [
            [['formId'], 'required'],
            [['formId'], 'validateFormHandleUniqueness'],
        ];
    }

    public function validateFormHandleUniqueness($attribute)
    {
        $metadata = $this->metadata;
        if (\is_string($metadata)) {
            $metadata = json_decode($this->metadata);
        }

        if (!isset($metadata->handle)) {
            return;
        }

        // Get the handle from the metadata
        $handle = $metadata->handle;

        // Check the database for existing records with the same formId and handle
        $exists = self::find()
            ->where(['formId' => $this->formId])
            ->andWhere(['like', 'metadata', '"handle":"'.$handle.'"'])
            ->andWhere(['NOT', ['uid' => $this->uid]])
            ->exists()
        ;

        // If a record exists with the same formId and handle, add an error
        if ($exists) {
            $this->addError('handle', 'Handle used more than once.');
        }
    }
}
