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

use Solspace\Freeform\Library\Exceptions\Integrations\ListNotFoundException;
use Solspace\Freeform\Library\Exceptions\Integrations\MailingListIntegrationNotFoundException;
use Solspace\Freeform\Library\Integrations\AbstractIntegration;
use Solspace\Freeform\Library\Integrations\MailingLists\AbstractMailingListIntegration;
use Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject;

interface MailingListHandlerInterface extends IntegrationHandlerInterface
{
    /**
     * Updates the mailing lists of a given mailing list integration.
     */
    public function updateLists(AbstractMailingListIntegration $integration, array $mailingLists): bool;

    /**
     * @return AbstractMailingListIntegration[]
     */
    public function getAllIntegrationObjects(): array;

    /**
     * @param int $id
     *
     * @return null|AbstractMailingListIntegration
     *
     * @throws MailingListIntegrationNotFoundException
     */
    public function getIntegrationObjectById($id);

    /**
     * Returns all ListObjects of a particular mailing list integration.
     *
     * @return ListObject[]
     */
    public function getLists(AbstractMailingListIntegration $integration): array;

    /**
     * @param int $id
     *
     * @throws ListNotFoundException
     */
    public function getListById(AbstractMailingListIntegration $integration, $id): ListObject;

    /**
     * Flag the given mailing list integration so that it's updated the next time it's accessed.
     */
    public function flagIntegrationForUpdating(AbstractIntegration $integration);
}
