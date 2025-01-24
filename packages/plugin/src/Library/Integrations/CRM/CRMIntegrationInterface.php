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

namespace Solspace\Freeform\Library\Integrations\CRM;

use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;

interface CRMIntegrationInterface
{
    /**
     * Get a list of all fields that can be filled by the form.
     *
     * @return FieldObject[]
     */
    public function getFields();

    /**
     * Push objects to the CRM.
     *
     * @param null|mixed $formFields
     *
     * @return bool
     */
    public function pushObject(array $keyValueList, $formFields = null);
}
