<?php

namespace Solspace\Freeform\Bundles\Fields\Implementations\FileUpload;

use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class FileRemovalOnSubmissionDelete extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            Submission::class,
            Submission::EVENT_AFTER_DELETE,
            [$this, 'removeSubmissionFiles']
        );
    }

    public function removeSubmissionFiles(Event $event): void
    {
        /** @var Submission $submission */
        $submission = $event->sender;
        $fields = $submission->getFieldCollection()->getList(FileUploadInterface::class);

        foreach ($fields as $field) {
            $value = $submission->getFormFieldValue($field);

            if (\is_array($value)) {
                foreach ($value as $id) {
                    if (\is_int($id)) {
                        \Craft::$app->elements->deleteElementById($id);
                    }
                }
            }
        }
    }
}
