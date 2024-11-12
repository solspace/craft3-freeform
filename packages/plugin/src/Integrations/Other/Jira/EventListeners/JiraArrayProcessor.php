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

namespace Solspace\Freeform\Integrations\Other\Jira\EventListeners;

use Solspace\Freeform\Events\Integrations\CrmIntegrations\ProcessValueEvent;
use Solspace\Freeform\Integrations\Other\Jira\JiraIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use Solspace\Freeform\Library\Integrations\DataObjects\FieldObject;
use yii\base\Event;

class JiraArrayProcessor extends FeatureBundle
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
        if (!$event->getIntegration() instanceof JiraIntegrationInterface) {
            return;
        }

        $integrationField = $event->getIntegrationField();
        if (FieldObject::TYPE_ARRAY !== $integrationField->getType()) {
            return;
        }

        $value = $event->getValue();
        if (\is_array($value)) {
            return;
        }

        if (!\is_string($value)) {
            $value = (string) $value;
        } else {
            $value = explode(',', $value);
            $value = array_map('trim', $value);
            $value = array_unique($value);
        }

        $event->setValue($value);
    }
}
