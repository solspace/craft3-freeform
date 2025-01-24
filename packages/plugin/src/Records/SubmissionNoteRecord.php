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
 * Class IntegrationsQueueRecord.
 *
 * @property int    $id
 * @property int    $submissionId
 * @property string $note
 */
class SubmissionNoteRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_submission_notes}}';

    public const NOTE_FIELD_NAME = 'freeform-submission-note';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
