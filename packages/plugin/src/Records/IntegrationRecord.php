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
 * Class IntegrationRecord.
 *
 * @property int       $id
 * @property string    $name
 * @property string    $handle
 * @property string    $type
 * @property string    $class
 * @property string    $accessToken
 * @property string    $settings
 * @property bool      $forceUpdate
 * @property \DateTime $lastUpdate
 */
class IntegrationRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_integrations}}';

    public const TYPE_MAILING_LIST = 'mailing_list';
    public const TYPE_CRM = 'crm';
    public const TYPE_PAYMENT_GATEWAY = 'payment_gateway';

    public static function tableName(): string
    {
        return self::TABLE;
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
