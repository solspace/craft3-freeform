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

namespace Solspace\Freeform\Models;

use craft\base\Model;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Records\MailingListFieldRecord;

/**
 * @property int    $id
 * @property int    $integrationId
 * @property string $resourceId
 * @property string $name
 * @property int    $memberCount
 */
class MailingListModel extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $integrationId;

    /** @var string */
    public $resourceId;

    /** @var string */
    public $name;

    /** @var int */
    public $memberCount;

    public static function create(): self
    {
        return new self();
    }

    /**
     * @return FieldObject[]
     */
    public function getFieldObjects(): array
    {
        /** @var MailingListFieldRecord[] $fields */
        $fields = MailingListFieldRecord::findAll(['mailingListId' => $this->id]);

        $fieldObjects = [];
        foreach ($fields as $field) {
            $fieldObjects[] = new FieldObject(
                $field->handle,
                $field->label,
                $field->type,
                $field->required
            );
        }

        return $fieldObjects;
    }
}
