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
 * @property int       $integrationId
 * @property int       $formId
 * @property bool      $enabled
 * @property string    $metadata
 * @property \DateTime $dateCreated
 * @property \DateTime $dateUpdated
 * @property string    $uid
 */
class FormIntegrationRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_forms_integrations}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [
            [['formId', 'integrationId'], 'unique', 'targetAttribute' => ['formId', 'integrationId']],
            [['formId', 'integrationId'], 'required'],
        ];
    }

    public function safeAttributes(): array
    {
        return [
            'formId',
            'integrationId',
            'enabled',
            'metadata',
        ];
    }
}
