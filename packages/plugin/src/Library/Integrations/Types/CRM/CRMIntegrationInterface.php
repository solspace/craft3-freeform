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

namespace Solspace\Freeform\Library\Integrations\Types\CRM;

use GuzzleHttp\Client;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use Solspace\Freeform\Library\Integrations\PushableInterface;

interface CRMIntegrationInterface extends APIIntegrationInterface, PushableInterface
{
    public const EVENT_ON_PUSH = 'on-push';

    /**
     * @return FieldObject[]
     */
    public function fetchFields(string $category, Client $client): array;
}
