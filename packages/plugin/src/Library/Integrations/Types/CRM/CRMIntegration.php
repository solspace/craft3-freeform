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

use Solspace\Freeform\Events\Integrations\PushEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Integrations\APIIntegration;
use yii\base\Event;

abstract class CRMIntegration extends APIIntegration implements CRMIntegrationInterface
{
    protected function getProcessableFields(string $category): array
    {
        return Freeform::getInstance()->crm->getFields($this, $category);
    }

    protected function triggerPushEvent(string $category, array $values): array
    {
        $event = new PushEvent($this, $category, $values);
        Event::trigger($this, self::EVENT_ON_PUSH, $event);

        return $event->getValues();
    }
}
