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
 * @property int    $id
 * @property string $name
 * @property string $description
 * @property string $fileName
 * @property string $body
 * @property int    $sortOrder
 */
class PdfTemplateRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_pdf_templates}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public function rules(): array
    {
        return [[['name', 'fileName'], 'required']];
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function safeAttributes(): array
    {
        return [
            'name',
            'description',
            'fileName',
            'body',
        ];
    }
}
