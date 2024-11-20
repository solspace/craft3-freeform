import type Freeform from '@components/front-end/plugin/freeform';
import events from '@lib/plugin/constants/event-types';
import { isEqual } from '@lib/plugin/helpers/comparisons';
import { dispatchCustomEvent } from '@lib/plugin/helpers/event-handling';
import type { FreeformHandler } from 'types/form';

export const enum Operator {
  Equals = 'equals',
  NotEquals = 'notEquals',
  GreaterThan = 'greaterThan',
  GreaterThanOrEquals = 'greaterThanOrEquals',
  LessThan = 'lessThan',
  LessThanOrEquals = 'lessThanOrEquals',
  Contains = 'contains',
  NotContains = 'notContains',
  StartsWith = 'startsWith',
  EndsWith = 'endsWith',
  IsEmpty = 'isEmpty',
  IsNotEmpty = 'isNotEmpty',
  IsOneOf = 'isOneOf',
  IsNotOneOf = 'isNotOneOf',
}

type FieldValue = string | string[] | number | boolean;

type RulesData = {
  values: Record<string, FieldValue>;
  rules: {
    fields: FieldRule[];
    buttons: ButtonRule[];
  };
};

type Rule = {
  display: 'show' | 'hide';
  combinator: 'and' | 'or';
  conditions: RuleCondition[];
};

type FieldRule = Rule & {
  field: string;
};

type ButtonRule = Rule & {
  button: string;
};

type RuleCondition = {
  field: string;
  operator: Operator;
  value: string;
};

const filterMatchingRules = (element: Element) => (rule: Rule) =>
  rule.conditions.some((condition) => {
    const elementName = (element as HTMLInputElement).name;
    const conditionName = condition.field;

    return conditionName === elementName || `${conditionName}[]` === elementName;
  });

class RuleHandler implements FreeformHandler {
  freeform: Freeform;
  form: HTMLFormElement;

  values: Record<string, FieldValue>;

  constructor(freeform: Freeform) {
    this.freeform = freeform;
    this.form = freeform.form as HTMLFormElement;

    this.reload();
  }

  reload = () => {
    const rulesElement = this.form.querySelector<HTMLDivElement>('[data-rules-json]');
    if (!rulesElement) {
      return;
    }

    const { rules, values }: RulesData = JSON.parse(rulesElement.dataset.rulesJson);
    this.values = values;
    if (rules.fields.length === 0 && rules.buttons.length === 0) {
      return;
    }

    // Iterate through all form elements
    Array.from(this.form.elements).forEach((element) => {
      // Find matching rules that are relying on this field
      const matchedFieldRules: FieldRule[] = rules.fields.filter(filterMatchingRules(element));
      const matchedButtonRules: ButtonRule[] = rules.buttons.filter(filterMatchingRules(element));

      const combinedRules = [...matchedFieldRules, ...matchedButtonRules];

      if (combinedRules.length === 0) {
        return;
      }

      let listener: string;
      switch (element.tagName) {
        case 'TEXTAREA':
        case 'INPUT':
          const input = element as HTMLInputElement;
          if (input.type === 'hidden') {
            return;
          }

          switch (input.type) {
            case 'radio':
            case 'checkbox':
              listener = 'change';
              break;

            default:
              listener = 'keyup';
              break;
          }

          break;

        case 'SELECT':
          listener = 'change';
          break;
      }

      if (!listener) {
        return;
      }

      // Create a callback which will be called when a field is changed
      const callback =
        (rule: FieldRule | ButtonRule): EventListenerOrEventListenerObject =>
        () => {
          // Trigger the main rule applying for this field
          this.applyRule(rule);

          // Trigger any related rules which are affected by this field
          // this allows for nested rules to work
          rules.fields.filter((r) => r !== rule).forEach((r) => this.applyRule(r));
        };

      // Attach event listeners
      combinedRules.forEach((rule) => {
        element.addEventListener(listener, callback(rule));
      });
    });

    // Trigger all rules on load
    rules.fields.forEach((rule) => this.applyRule(rule));
    rules.buttons.forEach((rule) => this.applyRule(rule));
  };

  applyRule = (rule: FieldRule | ButtonRule) => {
    const selector =
      'field' in rule ? `[data-field-container="${rule.field}"]` : `[data-button-container="${rule.button}"]`;

    const container = this.form.querySelector<HTMLDivElement>(selector);
    if (!container) {
      return;
    }

    const { display, combinator, conditions } = rule;

    // Either all conditions must be true, or at least one must be true
    // based on the combinator value
    const shouldDisplay =
      combinator === 'and' ? conditions.every(this.verifyCondition) : conditions.some(this.verifyCondition);

    // Change the `display` property in the styles based on the the rule's "show"/"hide" setting
    if (display === 'show') {
      container.style.display = shouldDisplay ? '' : 'none';
      if (shouldDisplay) {
        delete container.dataset.hidden;
      } else {
        container.dataset.hidden = '';
      }
    } else {
      container.style.display = shouldDisplay ? 'none' : '';
      if (!shouldDisplay) {
        delete container.dataset.hidden;
      } else {
        container.dataset.hidden = '';
      }
    }

    dispatchCustomEvent(events.rules.applied, { rule }, container);

    return true;
  };

  private verifyCondition = (condition: RuleCondition): boolean => {
    let currentValue: string | string[] | null = null;

    const fieldContainer = document.querySelector<HTMLDivElement>(`[data-field-container="${condition.field}"]`);
    if (fieldContainer) {
      const field = this.form[condition.field] || this.form[`${condition.field}[]`];

      const isCheckbox = fieldContainer.getAttribute('data-field-type') === 'checkbox';

      // Default the value to `null` if the field is hidden, which will help
      // with triggering nested rules
      const isHidden = fieldContainer.dataset.hidden !== undefined;

      if (isHidden) {
        currentValue = null;
      } else {
        if (isCheckbox) {
          const checkboxField = field[1] as HTMLInputElement;
          currentValue = checkboxField.checked ? '1' : '';
        } else if (field instanceof HTMLSelectElement && field.multiple) {
          currentValue = Array.from(field.options)
            .filter((option) => option.selected)
            .map((option) => option.value);
        } else if (field instanceof RadioNodeList) {
          currentValue = Array.from(field)
            .filter((checkbox) => (checkbox as HTMLInputElement).checked)
            .map((checkbox) => (checkbox as HTMLInputElement).value);
        } else {
          currentValue = field.value;
        }
      }
    } else {
      const storedValue = this.values[condition.field] || '';
      if (typeof storedValue === 'boolean') {
        currentValue = storedValue ? '1' : '';
      } else if (typeof storedValue === 'number') {
        currentValue = `${storedValue}`;
      } else {
        currentValue = storedValue;
      }
    }

    if (typeof currentValue === 'object') {
      switch (condition.operator) {
        case Operator.Equals:
          return isEqual(currentValue, [condition.value]);

        case Operator.NotEquals:
          return !isEqual(currentValue, [condition.value]);

        case Operator.Contains:
          return currentValue?.includes(condition.value);

        case Operator.NotContains:
          return !currentValue?.includes(condition.value);

        case Operator.IsEmpty:
          return currentValue === null || currentValue.length === 0;

        case Operator.IsNotEmpty:
          return currentValue?.length > 0;

        case Operator.IsOneOf:
        case Operator.IsNotOneOf:
          const checkPositive = condition.operator === Operator.IsOneOf;
          const parsedValue = (condition.value ? JSON.parse(condition.value) : []).map((v: string) => v.toLowerCase());
          const hasCommonValues = currentValue.some((value) => parsedValue.includes(value.toLowerCase()));

          if (parsedValue.length === 0) {
            return checkPositive ? currentValue.length !== 0 : currentValue.length === 0;
          }

          return checkPositive ? hasCommonValues : !hasCommonValues;

        default:
          return false;
      }
    }

    switch (condition.operator) {
      case Operator.Equals:
        return `${currentValue}`.toLowerCase() === `${condition.value}`.toLowerCase();

      case Operator.NotEquals:
        return `${currentValue}`.toLowerCase() !== `${condition.value}`.toLowerCase();

      case Operator.GreaterThan:
        return parseFloat(currentValue) > parseFloat(condition.value);

      case Operator.GreaterThanOrEquals:
        return parseFloat(currentValue) >= parseFloat(condition.value);

      case Operator.LessThan:
        return parseFloat(currentValue) < parseFloat(condition.value);

      case Operator.LessThanOrEquals:
        return parseFloat(currentValue) <= parseFloat(condition.value);

      case Operator.Contains:
        return `${currentValue}`.toLowerCase().includes(`${condition.value}`.toLowerCase());

      case Operator.NotContains:
        return !`${currentValue}`.toLowerCase().includes(`${condition.value}`.toLowerCase());

      case Operator.StartsWith:
        return `${currentValue}`.toLowerCase().startsWith(`${condition.value}`.toLowerCase());

      case Operator.EndsWith:
        return `${currentValue}`.toLowerCase().endsWith(`${condition.value}`.toLowerCase());

      case Operator.IsEmpty:
        return currentValue === null || currentValue.length === 0;

      case Operator.IsNotEmpty:
        return currentValue?.length > 0;

      case Operator.IsOneOf:
      case Operator.IsNotOneOf:
        const checkPositive = condition.operator === Operator.IsOneOf;
        const parsedValue = (condition.value ? JSON.parse(condition.value) : []).map((v: string) => v.toLowerCase());
        const containsValue = parsedValue.includes(currentValue.toLowerCase());

        if (parsedValue.length === 0) {
          return checkPositive ? currentValue.length === 0 : currentValue.length !== 0;
        }

        return checkPositive ? containsValue : !containsValue;

      default:
        return false;
    }
  };
}

export default RuleHandler;
