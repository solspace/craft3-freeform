<?php

namespace Solspace\Freeform\Bundles\Translations;

use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Form\Layout\Page;
use Solspace\Freeform\Services\Form\TranslationsService;

class TranslationProvider
{
    public function __construct(
        private TranslationsService $service,
    ) {}

    public function getTranslation(
        mixed $context,
        string $namespace,
        string $handle,
        mixed $defaultValue
    ): mixed {
        $form = $type = null;
        if ($context instanceof Form) {
            $form = $context;
            $type = TranslationsService::TYPE_FORM;
        } elseif ($context instanceof FieldInterface) {
            $form = $context->getForm();
            $type = TranslationsService::TYPE_FIELDS;
        } elseif ($context instanceof Page) {
            $form = $context->getForm();
            $type = TranslationsService::TYPE_PAGES;
        }

        if (!$form) {
            return $defaultValue;
        }

        return $this->service->getTranslation(
            $form,
            $type,
            $namespace,
            $handle,
            $defaultValue,
        );
    }
}
