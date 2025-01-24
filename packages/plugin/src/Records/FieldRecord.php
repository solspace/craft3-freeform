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
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Helpers\HashHelper;

/**
 * Class Freeform_FieldRecord.
 *
 * @property int    $id
 * @property string $type
 * @property string $handle
 * @property string $label
 * @property bool   $required
 * @property string $instructions
 * @property array  $metaProperties
 */
class FieldRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_fields}}';

    public const RESERVED_FIELD_KEYWORDS = [
        'id',
        'form',
        'title',
        'incrementalId',
        'statusId',
        'formId',
        'token',
        'ip',
        'isSpam',
        'dateCreated',
        'dateUpdated',
        'uid',
        'author',
        'level',
    ];

    /**
     * Returns the name of the associated database table.
     */
    public static function tableName(): string
    {
        return self::TABLE;
    }

    public static function create(): self
    {
        $field = new self();
        $field->type = AbstractField::TYPE_TEXT;

        return $field;
    }

    /**
     * Returns whether the current user can edit the element.
     */
    public function isEditable(): bool
    {
        return true;
    }

    public function getHash(): string
    {
        return HashHelper::hash($this->id);
    }

    /**
     * Depending on the field type - return its column type for the database.
     */
    public function getColumnType(): string
    {
        $columnType = 'text';

        switch ($this->type) {
            case FieldInterface::TYPE_SELECT:
            case FieldInterface::TYPE_CHECKBOX:
            case FieldInterface::TYPE_RADIO_GROUP:
            case FieldInterface::TYPE_NUMBER:
            case FieldInterface::TYPE_DATETIME:
                $columnType = 'varchar(255)';

                break;
        }

        return $columnType;
    }

    /**
     * {@inheritDoc}
     */
    public function rules(): array
    {
        return [
            [['handle'], 'unique'],
            [['handle'], 'checkReservedKeywords'],
        ];
    }

    /**
     * Validates an attribute to see if it's a reserved keyword or not.
     *
     * @param mixed $attribute
     */
    public function checkReservedKeywords($attribute)
    {
        $keyword = $this->{$attribute};

        if (\in_array($keyword, self::RESERVED_FIELD_KEYWORDS, true)) {
            $this->addError(
                $attribute,
                Freeform::t(
                    'The handle "{handle}" is a reserved keyword and cannot be used.',
                    ['handle' => $keyword, 'keywords' => implode('", "', self::RESERVED_FIELD_KEYWORDS)]
                )
            );
        }
    }
}
