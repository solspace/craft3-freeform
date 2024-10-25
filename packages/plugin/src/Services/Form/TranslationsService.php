<?php

namespace Solspace\Freeform\Services\Form;

use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Helpers\JsonHelper;
use Solspace\Freeform\Records\FormTranslationRecord;
use Solspace\Freeform\Services\BaseService;

class TranslationsService extends BaseService
{
    public const TYPE_FIELDS = 'fields';
    public const TYPE_PAGES = 'pages';
    public const TYPE_FORM = 'form';

    private array $translationCache = [];
    private ?bool $sitesEnabled = null;

    public function isTranslationsEnabled(Form $form): bool
    {
        if (null === $this->sitesEnabled) {
            $this->sitesEnabled = $this->getSettingsService()->getSettingsModel()->sitesEnabled;
        }

        if (!$form->getSettings()->getGeneral()->translations) {
            return false;
        }

        return $this->sitesEnabled;
    }

    public function getTranslation(
        Form $form,
        string $type,
        string $namespace,
        string $handle,
        string $defaultValue
    ): array|string {
        if (!$this->isTranslationsEnabled($form)) {
            return $defaultValue;
        }

        $siteId = \Craft::$app->sites->getCurrentSite()->id;
        $translationTable = $this->getFormTranslations($form);

        $translation = $translationTable->{$siteId}[$type][$namespace][$handle] ?? null;
        if (null === $translation) {
            return Freeform::t($defaultValue);
        }

        if (empty($translation)) {
            return $defaultValue;
        }

        return $translation;
    }

    public function getFormTranslations(Form $form): ?\stdClass
    {
        if (!$this->isTranslationsEnabled($form)) {
            return null;
        }

        if (!isset($this->translationCache[$form->getId()])) {
            $find = FormTranslationRecord::find()
                ->where(['formId' => $form->getId()])
                ->all()
            ;

            $translations = new \stdClass();
            foreach ($find as $found) {
                $translations->{$found->siteId} = $this->decodeTranslations($found->translations);
            }

            $this->translationCache[$form->getId()] = $translations;
        }

        return $this->translationCache[$form->getId()];
    }

    public function setFormTranslations(Form $form, array $translations): void
    {
        $existingTranslations = FormTranslationRecord::find()
            ->where(['formId' => $form->getId()])
            ->all()
        ;

        foreach ($existingTranslations as $existingTranslation) {
            $existingTranslation->delete();
        }

        foreach ($translations as $siteId => $translation) {
            $record = new FormTranslationRecord();
            $record->formId = $form->getId();
            $record->siteId = $siteId;
            $record->translations = JsonHelper::encode($translation);
            $record->save();
        }
    }

    private function decodeTranslations(string $translations): array
    {
        $decoded = json_decode($translations, true);
        foreach ($decoded as $type => $typeTranslations) {
            if (empty($typeTranslations)) {
                unset($decoded[$type]);

                continue;
            }

            foreach ($typeTranslations as $namespace => $namespaceTranslations) {
                if (empty($namespaceTranslations)) {
                    unset($decoded[$type][$namespace]);
                }
            }
        }

        return $decoded;
    }
}
