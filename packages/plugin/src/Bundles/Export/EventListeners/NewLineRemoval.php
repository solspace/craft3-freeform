<?php

namespace Solspace\Freeform\Bundles\Export\EventListeners;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportValueEvent;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class NewLineRemoval extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_VALUE,
            function (PrepareExportValueEvent $event) {
                $field = $event->getField();
                if (!$field instanceof TextareaField) {
                    return;
                }

                $settings = $event->getExporter()->getSettings();
                $isRemoveNewlines = $settings->isRemoveNewlines();
                if (!$isRemoveNewlines) {
                    return;
                }

                $value = $event->getValue();
                if (\is_string($value)) {
                    $value = trim(preg_replace('/\s+/', ' ', $value));
                    $event->setValue($value);
                }
            }
        );
    }
}
