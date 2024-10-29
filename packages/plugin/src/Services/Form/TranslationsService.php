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
        mixed $defaultValue
    ): mixed {
        if (!$this->isTranslationsEnabled($form)) {
            return $defaultValue;
        }

        $siteId = $this->getCurrentSiteId();

        $translationTable = $this->getFormTranslations($form);
        $translation = $translationTable->{$siteId}[$type][$namespace][$handle] ?? null;
        if (empty($translation)) {
            if (\is_string($defaultValue)) {
                return Freeform::t($defaultValue);
            }

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

    private function getCurrentSiteId(): int
    {
        static $siteId;

        if (null === $siteId) {
            $request = \Craft::$app->getRequest();
            $currentSite = \Craft::$app->getSites()->getCurrentSite();

            $siteId = $request->get('siteId');
            $siteHandle = $request->get('siteHandle', $request->get('site'));

            if ($siteId) {
                $siteId = (int) $siteId;
            } elseif ($siteHandle) {
                $site = \Craft::$app->getSites()->getSiteByHandle($siteHandle);
                if ($site) {
                    $siteId = $site->id;
                }
            }

            if (!$siteId) {
                $siteId = $currentSite->id;
            }
        }

        return $siteId;
    }
}
