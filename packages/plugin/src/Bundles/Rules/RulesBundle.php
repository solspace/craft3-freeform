<?php

namespace Solspace\Freeform\Bundles\Rules;

use Solspace\Freeform\Bundles\Form\Context\Pages\PageContext;
use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\PageJumpEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Rules\ProcessPostedRuleValueEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Attributes\Attributes;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\Serialization\FreeformSerializer;
use yii\base\Event;

class RulesBundle extends FeatureBundle
{
    public function __construct(
        private RuleProvider $ruleProvider,
        private RuleValidator $ruleValidator,
        private FreeformSerializer $serializer,
    ) {
        Event::on(
            Form::class,
            Form::EVENT_ATTACH_TAG_ATTRIBUTES,
            [$this, 'attachFormAttributes']
        );

        Event::on(
            Form::class,
            Form::EVENT_RENDER_BEFORE_CLOSING_TAG,
            [$this, 'attachRulesJSON']
        );

        Event::on(
            FieldInterface::class,
            FieldInterface::EVENT_VALIDATE,
            [$this, 'validateField']
        );

        Event::on(
            PageContext::class,
            PageContext::EVENT_PAGE_JUMP,
            [$this, 'handleFormPageJump']
        );

        Event::on(
            PageContext::class,
            PageContext::EVENT_PAGE_JUMP,
            [$this, 'handleFormSubmit']
        );
    }

    public static function getPriority(): int
    {
        return 50;
    }

    public function attachFormAttributes(AttachFormAttributesEvent $event): void
    {
        $form = $event->getForm();
        $rules = $this->ruleProvider->getFieldRules($form);

        if (empty($rules)) {
            return;
        }

        $form->getAttributes()->set('data-has-rules');
    }

    public function attachRulesJSON(RenderTagEvent $event): void
    {
        $values = [];
        foreach ($event->getForm()->getLayout()->getFields()->getStorableFields() as $field) {
            $processEvent = new ProcessPostedRuleValueEvent($field);
            Event::trigger(
                RuleValidator::class,
                RuleValidator::EVENT_PROCESS_POSTED_RULE_VALUE,
                $processEvent
            );

            $values[$field->getHandle()] = $processEvent->getValue();
        }

        $filterRules = fn ($rule) => $rule->getConditions()->count() > 0;

        $rules = [
            'values' => $values,
            'rules' => [
                'fields' => array_filter($this->ruleProvider->getFieldRules($event->getForm()), $filterRules),
                'buttons' => array_filter($this->ruleProvider->getButtonRules($event->getForm(), true), $filterRules),
            ],
        ];

        $serialized = $this->serializer->serialize(
            $rules,
            'json',
            ['groups' => 'front-end']
        );

        $attributes = new Attributes(['data-rules-json' => $serialized]);

        $event->addChunk("<div{$attributes}></div>");
    }

    public function validateField(ValidateEvent $event): void
    {
        $form = $event->getForm();
        $field = $event->getField();

        $isHidden = $this->ruleValidator->isFieldHidden($form, $field);
        if (true === $isHidden) {
            $event->isValid = false;
        }
    }

    public function handleFormPageJump(PageJumpEvent $event): void
    {
        $form = $event->getForm();
        $index = $this->ruleValidator->getPageJumpIndex($form);
        if (null === $index) {
            return;
        }

        $event->setJumpToIndex($index);
    }

    public function handleFormSubmit(PageJumpEvent $event): void
    {
        $form = $event->getForm();
        if (!$this->ruleValidator->isFormSubmittable($form)) {
            return;
        }

        $event->setJumpToIndex(-999);
    }
}
