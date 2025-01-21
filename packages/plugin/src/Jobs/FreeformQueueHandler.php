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

use craft\db\Query;
use craft\db\Table;
use craft\helpers\Queue;
use craft\queue\JobInterface;
use Solspace\Freeform\Services\SettingsService;

class FreeformQueueHandler
{
    private ?int $queuePriority;

    public function __construct(
        private SettingsService $settingsService
    ) {
        $this->queuePriority = $this->settingsService->getQueuePriority();
    }

    public function queueSingleJobInstance(JobInterface $job): void
    {
        if ($this->isJobInQueue($job)) {
            return;
        }

        Queue::push($job, $this->queuePriority);
    }

    public function executeNotificationJob(NotificationJobInterface $job): void
    {
        $queue = \Craft::$app->getQueue();

        if ($this->settingsService->isNotificationQueueEnabled()) {
            Queue::push($job, $this->queuePriority);
        } else {
            $job->execute($queue);
        }
    }

    public function executeIntegrationJob(IntegrationJobInterface $job): void
    {
        $queue = \Craft::$app->getQueue();

        if ($this->settingsService->isIntegrationQueueEnabled()) {
            Queue::push($job, $this->queuePriority);
        } else {
            $job->execute($queue);
        }
    }

    private function isJobInQueue(JobInterface $job): bool
    {
        $description = $job->getDescription();

        return (new Query())
            ->from(Table::QUEUE)
            ->where([
                'description' => $description,
                'fail' => false,
            ])
            ->andWhere(['dateReserved' => null])
            ->exists()
        ;
    }
}
