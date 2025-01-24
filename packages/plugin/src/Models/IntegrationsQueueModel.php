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

class IntegrationsQueueModel extends Model
{
    /** @var int */
    public $id;

    /** @var int */
    public $submissionId;

    /** @var string */
    public $fieldHash;

    /** @var string */
    public $integrationType;

    /** @var string */
    public $status;

    /** @var string */
    public $fieldValuesJson;
}
