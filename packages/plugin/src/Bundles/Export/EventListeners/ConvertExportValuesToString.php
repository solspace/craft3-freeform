<?php

namespace Solspace\Freeform\Bundles\Export\EventListeners;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportColumnEvent;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\StringHelper;
use yii\base\Event;

class ConvertExportValuesToString extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_COLUMN,
            [$this, 'onPrepareExportColumn']
        );
    }

    public function onPrepareExportColumn(PrepareExportColumnEvent $event): void
    {
        $submission = $event->getSubmission();
        $descriptor = $event->getFieldDescriptor();

        $value = $submission->{$descriptor->getId()};
        if ($value instanceof FieldInterface) {
            if ($value instanceof TableField) {
                $event->setValue($value);
            } else {
                $event->setValue($value->getValueAsString());
            }
        } elseif ($value instanceof \DateTime) {
            $event->setValue($value->format('Y-m-d H:i:s'));
        } else {
            if (\is_array($value)) {
                $event->setValue(StringHelper::implodeRecursively(', ', $value));
            }
        }
    }
}
