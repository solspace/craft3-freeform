// @ts-ignore
import ExpressionLanguage from 'expression-language';

const getVariablesPattern = /field:([a-zA-Z0-9_]+)/g;
const expressionLanguage = new ExpressionLanguage();

// Register sqrt function
expressionLanguage.register(
  'sqrt',
  // Compiler function - returns string representation
  (value: string) => {
    return `Math.sqrt(${value})`;
  },
  // Evaluator function - performs actual calculation
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  (args: Record<string, any>, value: number) => {
    if (typeof value !== 'number') {
      return value;
    }
    return Math.sqrt(value);
  }
);

const extractValue = (element: HTMLInputElement | HTMLSelectElement): string | number | boolean | null => {
  const value = element.value;

  // Return null if the value is an empty string
  if (value === '') {
    return null;
  }

  if (element.type === 'number') {
    return Number(value);
  }

  const lowercasedValue = value.toLowerCase();

  if (lowercasedValue === 'true') {
    return true;
  } else if (lowercasedValue === 'false') {
    return false;
  }

  return isNaN(Number(value)) ? value : Number(value);
};

const attachCalculations = (input: HTMLInputElement) => {
  const calculations = input.getAttribute('data-calculations');
  const decimal = input.getAttribute('data-decimal');

  // Get calculation logic & decimal count
  const calculationsLogic = calculations.replace(getVariablesPattern, (_, variable) => variable);
  const decimalCount = decimal ? Number(decimal) : null;

  // Get variables
  const variables: Record<string, string | number | boolean> = {};
  let match;
  while ((match = getVariablesPattern.exec(calculations)) !== null) {
    variables[match[1]] = '';
  }

  const handleCalculation = () => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    const isAllValuesFilled = Object.values(variables).every((value) => value !== null && value !== '');
    if (!isAllValuesFilled) {
      return;
    }

    let result = expressionLanguage.evaluate(calculationsLogic, variables);
    result = decimalCount !== null ? result.toFixed(decimalCount) : result;

    const updateInputValue = (value: string | number) => {
      input.value = value.toString();
      input.dispatchEvent(new Event('change'));
    };

    if (input.type !== 'hidden') {
      updateInputValue(result);
      return;
    }

    const container = input.parentElement;
    const pTag = container.querySelector('.freeform-calculation-plain-field');

    if (pTag) {
      pTag.textContent = result;
    }

    updateInputValue(result);
  };

  Object.keys(variables).forEach((variable) => {
    const inputElements = input.form.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
      `input[name="${variable}"], select[name="${variable}"]`
    );
    if (inputElements.length === 0) {
      return;
    }

    inputElements.forEach((element) => {
      const updateVariables = () => {
        if (element instanceof HTMLInputElement) {
          if (element.type === 'radio' && !element.checked) {
            return;
          }
          variables[variable] = extractValue(element);
        } else if (element instanceof HTMLSelectElement) {
          variables[variable] = extractValue(element);
        }
      };

      const updateVariablesAndCalculate = () => {
        updateVariables();
        handleCalculation();
      };

      updateVariables(); // Initial update

      if (element instanceof HTMLInputElement) {
        if (element.type === 'radio') {
          element.addEventListener('change', updateVariablesAndCalculate);
        } else {
          element.addEventListener('input', updateVariablesAndCalculate);
        }
      } else if (element instanceof HTMLSelectElement) {
        element.addEventListener('change', updateVariablesAndCalculate);
      }
    });
  });

  // Trigger initial calculation if all values are present
  const areDefaultValuesSet = Object.keys(variables).every((variable) => {
    const inputElements = input.form.querySelectorAll<HTMLInputElement | HTMLSelectElement>(
      `input[name="${variable}"], select[name="${variable}"]`
    );

    if (inputElements.length === 0) {
      return false; // No matching inputs found for the variable
    }

    let value: string | number | boolean | null = null;

    inputElements.forEach((element) => {
      if (element instanceof HTMLInputElement) {
        if (element.type === 'radio') {
          if (element.checked) {
            value = extractValue(element);
          }
        } else {
          value = extractValue(element);
        }
      } else if (element instanceof HTMLSelectElement) {
        value = extractValue(element);
      }
    });

    variables[variable] = value;

    // Ensure the variable has a non-null, non-empty value
    return value !== null && value !== '';
  });

  if (areDefaultValuesSet) {
    handleCalculation();
  }
};

const registerCalculationInputs = async (container: HTMLElement) => {
  const input = container.querySelector<HTMLInputElement>('input[data-calculations]');
  if (!input) {
    return;
  }

  attachCalculations(input);
};

document.querySelectorAll<HTMLInputElement>('*[data-field-type=calculation]').forEach(registerCalculationInputs);

const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
      mutation.addedNodes.forEach((node) => {
        if (node instanceof HTMLElement) {
          const input = node.querySelector<HTMLInputElement>('input[data-calculations]');
          if (input) {
            attachCalculations(input);
          }
        }
      });
    }
  });
});

observer.observe(document.body, { childList: true, subtree: true });
