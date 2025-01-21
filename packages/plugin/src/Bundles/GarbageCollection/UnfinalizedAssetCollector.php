<?php

namespace Solspace\Freeform\Bundles\GarbageCollection;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\PurgeUnfinalizedAssetsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Application;
use yii\base\Event;

class UnfinalizedAssetCollector extends FeatureBundle
{
    private const CACHE_KEY = 'freeform-unfinalized-assets';
    private const CACHE_TTL = 60 * 60; // 1h

    public function __construct(
        private SettingsService $settings,
        private FreeformQueueHandler $queueHandler,
    ) {
        if (\Craft::$app->request->isConsoleRequest) {
            return;
        }

        Event::on(
            Application::class,
            Application::EVENT_AFTER_REQUEST,
            [$this, 'removeUnfinalizedAssets']
        );
    }

    public function removeUnfinalizedAssets(): void
    {
        if (Freeform::isLocked(self::CACHE_KEY, self::CACHE_TTL)) {
            return;
        }

        $purgeAssetsEnabled = $this->settings->getSettingsModel()->purgeAssets;
        if (!$purgeAssetsEnabled) {
            return;
        }

        $assetAge = $this->settings->getPurgableUnfinalizedAssetAgeInMinutes();
        if ($assetAge > 0) {
            $this->queueHandler->queueSingleJobInstance(new PurgeUnfinalizedAssetsJob(['age' => $assetAge]));
        }
    }
}
