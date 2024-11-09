<?php

namespace Solspace\Freeform\Bundles\Persistence;

use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\controllers\api\FormsController;
use Solspace\Freeform\Events\Forms\PersistFormEvent;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Records\FormTranslationRecord;
use yii\base\Event;

class TranslationPersistence extends FeatureBundle
{
    public function __construct(private PropertyProvider $propertyProvider)
    {
        Event::on(
            FormsController::class,
            FormsController::EVENT_UPSERT_FORM,
            [$this, 'handleTranslationSave']
        );
    }

    public static function getPriority(): int
    {
        return 400;
    }

    public function handleTranslationSave(PersistFormEvent $event): void
    {
        if ($event->hasErrors()) {
            return;
        }

        $siteTranslations = $event->getPayload()->translations ?? null;
        if (null === $siteTranslations) {
            return;
        }

        /** @var FormTranslationRecord[] $record */
        $existingRecords = FormTranslationRecord::find()
            ->where(['formId' => $event->getFormId()])
            ->indexBy('siteId')
            ->all()
        ;

        foreach ($siteTranslations as $siteId => $translations) {
            if (null === $translations) {
                if ($existingRecords[$siteId] ?? false) {
                    $existingRecords[$siteId]->delete();
                }

                continue;
            }

            $fieldTranslations = $translations->fields ?? [];
            foreach ($fieldTranslations as $uid => $fieldTranslation) {
                if (!$event->getFieldRecord($uid)) {
                    unset($fieldTranslations->{$uid});
                }
            }

            $translations->fields = $fieldTranslations;

            $record = $existingRecords[$siteId] ?? new FormTranslationRecord();
            $record->formId = $event->getFormId();
            $record->siteId = $siteId;
            $record->translations = json_encode($translations);
            $record->save();
        }
    }
}
