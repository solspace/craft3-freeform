<?php

namespace Solspace\Freeform\Bundles\Export\EventListeners;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportValueEvent;
use Solspace\Freeform\Bundles\Export\Interfaces\StringValueExportInterface;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Helpers\StringHelper;
use yii\base\Event;

class ConvertValuesToString extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_VALUE,
            [$this, 'convertValueToString'],
        );
    }

    public static function getPriority(): int
    {
        return 500;
    }

    public function convertValueToString(PrepareExportValueEvent $event): void
    {
        if (!$event->getExporter() instanceof StringValueExportInterface) {
            return;
        }

        $field = $event->getField();
        $value = $event->getValue();

        if ($field instanceof FieldInterface) {
            if (!$field instanceof TableField) {
                $value = $field->getValueAsString();
            }
        } elseif (\is_array($value) || \is_object($value)) {
            $value = StringHelper::implodeRecursively(', ', (array) $value);
        }

        $event->setValue($value);
    }
}
