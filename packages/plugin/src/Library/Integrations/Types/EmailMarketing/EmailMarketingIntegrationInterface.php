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

namespace Solspace\Freeform\Library\Integrations\Types\EmailMarketing;

use GuzzleHttp\Client;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;
use Solspace\Freeform\Library\Integrations\PushableInterface;
use Solspace\Freeform\Library\Integrations\Types\EmailMarketing\DataObjects\ListObject;

interface EmailMarketingIntegrationInterface extends IntegrationInterface, PushableInterface
{
    public static function isInstallable(): bool;

    public function fetchLists(Client $client): array;

    public function fetchFields(ListObject $list, string $category, Client $client): array;
}
