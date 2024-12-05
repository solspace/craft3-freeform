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

namespace Solspace\Freeform\Integrations\CRM\Salesforce\EventListeners;

use Solspace\Freeform\Events\Integrations\CrmIntegrations\ProcessValueEvent;
use Solspace\Freeform\Integrations\CRM\Salesforce\SalesforceIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use yii\base\Event;

class SalesforceCheckboxProcessor extends FeatureBundle
{
    private const NEGATIVE_VALUES = ['no', '', 'n', 'false', 0, '0', false, null];

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
        if (!$event->getIntegration() instanceof SalesforceIntegrationInterface) {
            return;
        }

        $integrationField = $event->getIntegrationField();
        if (FieldObject::TYPE_BOOLEAN !== $integrationField->getType()) {
            return;
        }

        $value = $event->getValue();
        if (\is_bool($value)) {
            return;
        }

        if (\is_array($value)) {
            $event->setValue(!empty($value));

            return;
        }

        if (\is_string($value)) {
            $value = strtolower($value);
        }

        $event->setValue(!\in_array($value, self::NEGATIVE_VALUES, true));
    }
}
