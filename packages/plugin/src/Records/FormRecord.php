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

/**
 * Class Freeform_FormRecord.
 *
 * @property int    $id
 * @property string $type
 * @property string $metadata
 * @property string $name
 * @property string $handle
 * @property int    $spamBlockCount
 * @property string $submissionTitleFormat
 * @property string $description
 * @property string $layoutJson
 * @property string $returnUrl
 * @property string $extraPostUrl
 * @property string $extraPostTriggerPhrase
 * @property int    $defaultStatus
 * @property int    $formTemplateId
 * @property int    $optInDataStorageTargetHash
 * @property int    $limitFormSubmissions
 * @property string $color
 * @property int    $order
 * @property bool   $gtmEnabled
 * @property string $gtmId
 * @property string $gtmEventName
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
