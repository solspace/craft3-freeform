<?php

namespace Solspace\Freeform\Bundles\Export\EventListeners;

use Solspace\Freeform\Bundles\Export\Events\PrepareExportValueEvent;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class FileUrlFormatter extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            SubmissionExportInterface::class,
            SubmissionExportInterface::EVENT_PREPARE_EXPORT_VALUE,
            [$this, 'formatFileUrl'],
        );
    }

    public static function getPriority(): int
    {
        return 400;
    }

    public function formatFileUrl(PrepareExportValueEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof FileUploadField) {
            return;
        }

        $assets = $field->getAssets()->all();

        $urls = [];
        foreach ($assets as $asset) {
            $urls[] = $asset->getUrl() ?: $asset->getFilename() ?: $asset->id;
        }

        $event->setValue($urls);
    }
}
