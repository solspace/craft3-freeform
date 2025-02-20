<?php

namespace Solspace\Freeform\Bundles\Rules;

use Solspace\Freeform\Events\Rules\ProcessPostedRuleValueEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Collections\PageCollection;
use Solspace\Freeform\Library\Rules\Rule;
use Solspace\Freeform\Library\Rules\RuleInterface;
use Solspace\Freeform\Library\Rules\Types\ButtonRule;
use Solspace\Freeform\Library\Rules\Types\FieldRule;
use yii\base\Event;

class RuleValidator
{
    public const EVENT_PROCESS_POSTED_RULE_VALUE = 'process-posted-rule-value';

    public function __construct(
        private RuleProvider $ruleProvider,
        private ConditionValidator $conditionValidator,
    ) {}

    public function isFieldHidden(Form $form, FieldInterface $field): bool
    {
        $rule = $this->ruleProvider->getFieldRule($form, $field);
        if (!$rule) {
            return false;
        }

        if (0 === $rule->getConditions()->count()) {
            return false;
        }

        $shouldShow = FieldRule::DISPLAY_SHOW === $rule->getDisplay();
        $triggersRule = $this->triggersRule($form, $rule);

        return $shouldShow ? !$triggersRule : $triggersRule;
    }

    public function isButtonHidden(Form $form, string $button): bool
    {
        $rule = $this->ruleProvider->getButtonRule($form, $button);
        if (!$rule) {
            return false;
        }

        if (0 === $rule->getConditions()->count()) {
            return false;
        }

        $shouldShow = ButtonRule::DISPLAY_SHOW === $rule->getDisplay();
        $triggersRule = $this->triggersRule($form, $rule);

        return $shouldShow ? !$triggersRule : $triggersRule;
    }

    public function getPageJumpIndex(Form $form): ?int
    {
        $rules = $this->ruleProvider->getPageRules($form);
        $currentPage = $form->getCurrentPage();

        foreach ($rules as $rule) {
            foreach ($rule->getConditions() as $condition) {
                if (!$currentPage->getFields()->get($condition->getField())) {
                    return null;
                }
            }

            if ($this->triggersRule($form, $rule)) {
                return $rule->getPage()->getIndex();
            }
        }

        return null;
    }

    public function isFormSubmittable(Form $form): bool
    {
        $rule = $this->ruleProvider->getSubmitFormRule($form);
        if (!$rule) {
            return false;
        }

        $currentPage = $form->getCurrentPage();

        $availablePages = new PageCollection();
        foreach ($form->getPages() as $page) {
            if ($page->getIndex() > $currentPage->getIndex()) {
                break;
            }

            $availablePages->add($page);
        }

        return $this->triggersRule($form, $rule, $availablePages);
    }

    private function triggersRule(Form $form, RuleInterface $rule, ?PageCollection $availablePages = null): bool
    {
        $conditions = $rule->getConditions();

        $matchesSome = false;
        $matchesAll = true;
        foreach ($conditions as $condition) {
            if ($availablePages) {
                $hasField = false;
                foreach ($availablePages as $page) {
                    if ($page->getFields()->has($condition->getField())) {
                        $hasField = true;

                        break;
                    }
                }

                if (!$hasField) {
                    continue;
                }
            }

            $isConditionFieldHidden = $this->isFieldHidden($form, $condition->getField());
            if ($isConditionFieldHidden) {
                $postedValue = null;
            } else {
                $event = new ProcessPostedRuleValueEvent($condition->getField());
                Event::trigger($this, self::EVENT_PROCESS_POSTED_RULE_VALUE, $event);

                $postedValue = $event->getValue();
            }

            $valueMatch = $this->conditionValidator->validate($condition, $postedValue);
            if ($valueMatch) {
                $matchesSome = true;
            } else {
                $matchesAll = false;
            }
        }

        return match ($rule->getCombinator()) {
            Rule::COMBINATOR_AND => $matchesAll,
            Rule::COMBINATOR_OR => $matchesSome,
        };
    }
}
