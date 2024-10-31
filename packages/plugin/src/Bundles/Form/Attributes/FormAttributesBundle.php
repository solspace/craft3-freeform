<?php

namespace Solspace\Freeform\Bundles\Form\Attributes;

use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\SetPropertiesEvent;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class FormAttributesBundle extends FeatureBundle
{
    public function __construct(private TranslationProvider $translationProvider)
    {
        Event::on(Form::class, Form::EVENT_SET_PROPERTIES, [$this, 'separateAttributesFromProperties']);
        Event::on(Form::class, Form::EVENT_ATTACH_TAG_ATTRIBUTES, [$this, 'setConditionalAttributes']);
    }

    public function separateAttributesFromProperties(SetPropertiesEvent $event): void
    {
        $form = $event->getForm();
        $properties = $event->getProperties();

        if (!isset($properties['attributes'])) {
            return;
        }

        $attributes = $properties['attributes'];
        unset($properties['attributes']);

        $event->setProperties($properties);

        if ($form->isFormPosted()) {
            return;
        }

        if (!\is_array($attributes)) {
            return;
        }

        $form->getAttributes()->merge($attributes);
        if (null === $form->getAttributes()->get('method')) {
            $form->getAttributes()->set('method', 'post');
        }
    }

    public function setConditionalAttributes(AttachFormAttributesEvent $event): void
    {
        $formService = Freeform::getInstance()->forms;

        $form = $event->getForm();
        $attributes = $form->getAttributes();

        $behaviorSettings = $form->getSettings()->getBehavior();

        $attributes->replace('data-freeform', true);
        $attributes->replace('data-disable-reset', $form->isAjaxResetDisabled());
        $attributes->replace('data-id', $form->getAnchor());
        $attributes->replace('data-handle', $form->getHandle());
        $attributes->replace('data-ajax', $form->isAjaxEnabled());
        $attributes->replace('data-disable-submit', $formService->isFormSubmitDisable());
        $attributes->replace('data-show-processing-spinner', $behaviorSettings->showProcessingSpinner);

        if (null === $attributes->get('method')) {
            $attributes->set('method', 'post');
        }

        if ($form->getLayout()->getFields(FileUploadInterface::class)->count()) {
            $attributes->replace('enctype', 'multipart/form-data');
        }

        $autoScroll = Freeform::getInstance()->settings->getSettingsModel()->autoScroll;
        if ($autoScroll) {
            $attributes->replace('data-auto-scroll', $autoScroll);
        }

        if ($formService->shouldScrollToAnchor($form)) {
            $attributes->replace('data-scroll-to-anchor', true);
        }

        if ($behaviorSettings->showProcessingText) {
            $attributes->replace('data-show-processing-text', true);
            $attributes->replace(
                'data-processing-text',
                $this->translationProvider
                    ->getTranslation(
                        $form,
                        'behavior',
                        'processingText',
                        $behaviorSettings->processingText,
                    )
            );
        }

        $attributes->replace(
            'data-success-message',
            $this->translationProvider
                ->getTranslation(
                    $form,
                    'behavior',
                    'successMessage',
                    $behaviorSettings->getSuccessMessage(),
                )
        );

        $attributes->replace(
            'data-error-message',
            $this->translationProvider
                ->getTranslation(
                    $form,
                    'behavior',
                    'errorMessage',
                    $behaviorSettings->getErrorMessage(),
                )
        );
    }
}
