<?php

namespace Solspace\Freeform\Bundles\Export\EventListeners;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportValueEvent;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class DateFormatter extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_VALUE,
            [$this, 'formatDate'],
        );
    }

    public static function getPriority(): int
    {
        return 400;
    }

    public function formatDate(PrepareExportValueEvent $event): void
    {
        $value = $event->getValue();

        if ($value instanceof \DateTime) {
            $event->setValue($value->format('Y-m-d H:i:s'));
        }
    }
}
