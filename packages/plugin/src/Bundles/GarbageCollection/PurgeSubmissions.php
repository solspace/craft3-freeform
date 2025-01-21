<?php

namespace Solspace\Freeform\Bundles\GarbageCollection;

use Solspace\Freeform\Freeform;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\PurgeSpamJob;
use Solspace\Freeform\Jobs\PurgeSubmissionsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\SettingsService;
use yii\base\Application;
use yii\base\Event;

class PurgeSubmissions extends FeatureBundle
{
    private const CACHE_KEY = 'freeform-purge-submissions';
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

        $submissionAge = $this->settings->getPurgableSubmissionAgeInDays();
        if ($submissionAge > 0) {
            $this->queueHandler->queueSingleJobInstance(new PurgeSubmissionsJob(['age' => $submissionAge]));
        }

        $spamAge = $this->settings->getPurgableSpamAgeInDays();
        if ($spamAge > 0) {
            $this->queueHandler->queueSingleJobInstance(new PurgeSpamJob(['age' => $spamAge]));
        }
    }
}
