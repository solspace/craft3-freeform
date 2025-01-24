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

namespace Solspace\Freeform\Library\Database;

use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Library\Exceptions\Integrations\CRMIntegrationNotFoundException;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\CRM\AbstractCRMIntegration;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;

interface CRMHandlerInterface extends IntegrationHandlerInterface
{
    /**
     * @return AbstractCRMIntegration[]
     */
    public function getAllIntegrationObjects(): array;

    /**
     * @param int $id
     *
     * @return null|AbstractCRMIntegration
     *
     * @throws CRMIntegrationNotFoundException
     */
    public function getIntegrationObjectById($id);

    /**
     * Updates the fields of a given CRM integration.
     *
     * @param FieldObject[] $fields
     */
    public function updateFields(AbstractCRMIntegration $integration, array $fields): bool;

    /**
     * Returns all FieldObjects of a particular CRM integration.
     *
     * @return FieldObject[]
     */
    public function getFields(AbstractCRMIntegration $integration): array;

    /**
     * Flag the given CRM integration so that it's updated the next time it's accessed.
     */
    public function flagIntegrationForUpdating(AbstractIntegration $integration);

    /**
     * Push the mapped object values to the CRM.
     */
    public function pushObject(Submission $submission): bool;
}
