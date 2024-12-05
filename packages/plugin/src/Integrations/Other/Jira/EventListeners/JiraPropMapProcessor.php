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
use Solspace\Freeform\Integrations\Other\Jira\Cards\JiraCards;
use Solspace\Freeform\Integrations\Other\Jira\JiraIntegrationInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;
use yii\base\Event;

class JiraPropMapProcessor extends FeatureBundle
{
    private const PROPERTY_MAP = [
        JiraCards::TYPE_KEY_MAP => 'key',
        JiraCards::TYPE_NAME_MAP => 'name',
        JiraCards::TYPE_ID_MAP => 'id',
    ];

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
        if (!\in_array($integrationField->getType(), array_keys(self::PROPERTY_MAP), true)) {
            return;
        }

        $value = $event->getValue();
        $value = [
            self::PROPERTY_MAP[$integrationField->getType()] => $value,
        ];

        $event->setValue($value);
    }
}
