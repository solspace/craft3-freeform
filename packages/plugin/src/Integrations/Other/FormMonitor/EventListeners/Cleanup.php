<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use craft\helpers\Queue;
use Solspace\Freeform\Integrations\Other\FormMonitor\Jobs\FormMonitorCleanupJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;

class Cleanup extends FeatureBundle
{
    private const CLEANUP_KEY = 'form-monitor-cleanup';
    private const CLEANUP_TTL = 60 * 60 * 24; // 1 day

    public function __construct()
    {
        $plugin = $this->plugin();

        $isLocked = $plugin->lock->isLocked(self::CLEANUP_KEY, self::CLEANUP_TTL);
        if ($isLocked) {
            return;
        }

        Queue::push(new FormMonitorCleanupJob());
    }
}
