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

class JiraJDocProcessor extends FeatureBundle
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
        if (JiraCards::TYPE_JDOC !== $integrationField->getType()) {
            return;
        }

        $value = $event->getValue();
        $value = [
            'content' => [
                [
                    'content' => [
                        [
                            'text' => $value,
                            'type' => 'text',
                        ],
                    ],
                    'type' => 'paragraph',
                ],
            ],
            'type' => 'doc',
            'version' => 1,
        ];

        $event->setValue($value);
    }
}
