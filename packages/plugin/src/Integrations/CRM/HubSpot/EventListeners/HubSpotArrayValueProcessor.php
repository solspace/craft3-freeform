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

namespace Solspace\Freeform\Integrations\CRM\HubSpot\EventListeners;

use Solspace\Freeform\Events\Integrations\CrmIntegrations\ProcessValueEvent;
use Solspace\Freeform\Integrations\CRM\HubSpot\HubSpotIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use yii\base\Event;

class HubSpotArrayValueProcessor extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            APIIntegrationInterface::class,
            APIIntegrationInterface::EVENT_PROCESS_VALUE,
            [$this, 'processValue']
        );
    }

    public function processValue(ProcessValueEvent $event): void
    {
        if (!$event->getIntegration() instanceof HubSpotIntegrationInterface) {
            return;
        }

        $integrationField = $event->getIntegrationField();
        if (FieldObject::TYPE_ARRAY !== $integrationField->getType()) {
            return;
        }

        $value = $event->getValue();
        if (!\is_array($value)) {
            return;
        }

        $value = implode(';', $value);
        $event->setValue($value);
    }
}
