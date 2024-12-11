<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use craft\db\Query;
use craft\helpers\Queue;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Integrations\Other\FormMonitor\Jobs\FormMonitorCleanupJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\IntegrationRecord;

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

        try {
            $hasFormMonitorEnabled = (bool) (new Query())
                ->select('fi.[[id]]')
                ->from(FormIntegrationRecord::TABLE.' fi')
                ->innerJoin(IntegrationRecord::TABLE.' i', 'i.[[id]] = fi.[[integrationId]]')
                ->where([
                    'fi.[[enabled]]' => true,
                    'i.[[class]]' => FormMonitor::class,
                ])
                ->count()
            ;
        } catch (\Exception $e) {
            $hasFormMonitorEnabled = false;
        }

        if (!$hasFormMonitorEnabled) {
            return;
        }

        Queue::push(new FormMonitorCleanupJob());
    }
}
