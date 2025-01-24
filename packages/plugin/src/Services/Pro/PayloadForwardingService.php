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

namespace Solspace\Freeform\Services\Pro;

use Solspace\Freeform\Services\BaseService;

/**
 * @deprecated will be removed in v4, use the PayloadForwarding bundle events instead
 */
class PayloadForwardingService extends BaseService
{
    /**
     * @deprecated use PayloadForwarding::EVENT_PAYLOAD_FORWARDING instead
     */
    public const BEFORE_PAYLOAD_FORWARD = 'beforePayloadForward';
}
