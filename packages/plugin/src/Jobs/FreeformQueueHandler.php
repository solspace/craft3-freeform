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

namespace Solspace\Freeform\Jobs;

use craft\helpers\Queue;
use Solspace\Freeform\Services\SettingsService;

class FreeformQueueHandler
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    public function executeNotificationJob(NotificationJobInterface $job): void
    {
        $queue = \Craft::$app->getQueue();

        if ($this->settingsService->isNotificationQueueEnabled()) {
            Queue::push($job, $this->settingsService->getQueuePriority());
        } else {
            $job->execute($queue);
        }
    }

    public function executeIntegrationJob(IntegrationJobInterface $job): void
    {
        $queue = \Craft::$app->getQueue();

        if ($this->settingsService->isIntegrationQueueEnabled()) {
            Queue::push($job, $this->settingsService->getQueuePriority());
        } else {
            $job->execute($queue);
        }
    }
}
