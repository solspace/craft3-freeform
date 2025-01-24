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

namespace Solspace\Freeform\Library\Integrations\MailingLists;

use Solspace\Freeform\Library\Integrations\MailingLists\DataObjects\ListObject;

interface MailingListIntegrationInterface
{
    public static function isInstallable(): bool;

    /**
     * @return ListObject[]
     */
    public function getLists();

    /**
     * @param string $listId
     *
     * @return ListObject
     */
    public function getListById($listId);

    /**
     * Push emails to a specific mailing list for the service provider.
     *
     * @param array $mappedValues - key => value pairs of integrations fields against form fields
     *
     * @return bool
     */
    public function pushEmails(ListObject $mailingList, array $emails, array $mappedValues);
}
