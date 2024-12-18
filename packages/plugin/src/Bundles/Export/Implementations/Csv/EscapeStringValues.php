<?php

namespace Solspace\Freeform\Bundles\Export\Implementations\Csv;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportColumnEvent;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class EscapeStringValues extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_COLUMN,
            [$this, 'escapeStringValues']
        );
    }

    public static function getPriority(): int
    {
        return 1000;
    }

    public function escapeStringValues(PrepareExportColumnEvent $event): void
    {
        $value = $event->getValue();
        if (\is_string($value)) {
            $event->setValue('"'.str_replace('"', '""', $value).'"');
        }
    }
}
