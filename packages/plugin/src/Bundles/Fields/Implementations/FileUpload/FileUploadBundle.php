<?php

namespace Solspace\Freeform\Bundles\Fields\Implementations\FileUpload;

use Carbon\Carbon;
use Solspace\Freeform\Bundles\Form\SaveForm\Events\SaveFormEvent;
use Solspace\Freeform\Bundles\Form\SaveForm\SaveForm;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Fields\TransformValueEvent;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Events\Submissions\RenderTableValueEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\Pro\FileDragAndDropField;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Records\UnfinalizedFileRecord;
use yii\base\Event;

class FileUploadBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(Form::class, Form::EVENT_AFTER_SUBMIT, [$this, 'finalizeFiles']);
        Event::on(FieldInterface::class, FieldInterface::EVENT_TRANSFORM_FROM_POST, [$this, 'handleDnDPost']);
        Event::on(Submission::class, Submission::EVENT_RENDER_TABLE_VALUE, [$this, 'renderTableValue']);
        Event::on(SaveForm::class, SaveForm::EVENT_SAVE_FORM, [$this, 'prolongUnfinalizedAssets']);
    }

    public function prolongUnfinalizedAssets(SaveFormEvent $event): void
    {
        $saveTimeDays = (int) Freeform::getInstance()->settings->getSettingsModel()->saveFormTtl;
        $newDate = new Carbon('now +'.$saveTimeDays.' days', 'UTC');

        $form = $event->getForm();

        $records = $this->getUnfinalizedFileRecords($form);
        foreach ($records as $record) {
            $record->dateCreated = $newDate;
            $record->dateUpdated = $newDate;
            $record->save();
        }
    }

    public function finalizeFiles(SubmitEvent $event): void
    {
        $form = $event->getForm();

        // Handle only finished forms
        if (!$event->getForm()->isFinished()) {
            return;
        }

        $records = $this->getUnfinalizedFileRecords($form);
        foreach ($records as $record) {
            $record->delete();
        }
    }

    public function handleDnDPost(TransformValueEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof FileDragAndDropField) {
            return;
        }

        $uids = $event->getValue();
        if (!\is_array($uids)) {
            $event->setValue([]);

            return;
        }

        $ids = [];
        foreach ($uids as $uid) {
            $asset = \Craft::$app->getElements()->getElementByUid($uid);
            if ($asset) {
                $ids[] = $asset->id;
            }
        }

        $event->setValue($ids);
    }

    public function renderTableValue(RenderTableValueEvent $event): void
    {
        $field = $event->getField();
        if (!$field instanceof FileUploadField) {
            return;
        }

        $output = '<div class="inline-chips">';
        foreach ($field->getValue() as $assetId) {
            $asset = \Craft::$app->assets->getAssetById((int) $assetId);

            if ($asset) {
                $output .= \Craft::$app->view->renderTemplate(
                    'freeform/_components/fields/file.html',
                    ['asset' => $asset]
                );
            }
        }
        $output .= '</div>';

        $event->setOutput($output);
    }

    /**
     * @return UnfinalizedFileRecord[]
     */
    private function getUnfinalizedFileRecords(Form $form): array
    {
        $fields = $form->getLayout()->getFields(FileUploadField::class);
        $assetIds = [];
        foreach ($fields as $field) {
            $assetIds = array_merge($assetIds, $field->getValue() ?? []);
        }

        if (empty($assetIds)) {
            return [];
        }

        return UnfinalizedFileRecord::findAll(['assetId' => $assetIds]);
    }
}
