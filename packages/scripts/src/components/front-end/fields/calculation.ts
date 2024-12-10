// @ts-ignore
import ExpressionLanguage from 'expression-language';

const getVariablesPattern = /field:([a-zA-Z0-9_]+)/g;
const expressionLanguage = new ExpressionLanguage();

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
  const decimalCount = decimal ?? Number(decimal);

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
    result = decimalCount ? result.toFixed(decimalCount) : result;

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
    const inputElements = input.form.querySelectorAll(`input[name="${variable}"], select[name="${variable}"]`);
    if (inputElements.length === 0) {
      return;
    }

    const element = inputElements[0] as HTMLInputElement | HTMLSelectElement;

    const updateVariables = () => {
      variables[variable] = extractValue(element);
    };

    const updateVariablesAndCalculate = () => {
      updateVariables();
      handleCalculation();
    };

    updateVariables(); // Initial update

    if (element instanceof HTMLInputElement) {
      element.addEventListener('input', updateVariablesAndCalculate);
    } else if (element instanceof HTMLSelectElement) {
      element.addEventListener('change', updateVariablesAndCalculate);
    }

    // Handling other input elements (if any)
    if (inputElements.length > 1) {
      inputElements.forEach((element) => {
        if (element !== element && element instanceof HTMLInputElement) {
          element.addEventListener('click', () => {
            variables[variable] = extractValue(element);
            handleCalculation();
          });
        }
      });
    }
  });

  // Trigger initial calculation if all values are present
  const areDefaultValuesSet = Object.keys(variables).every((variable) => {
    const inputElement = input.form.querySelector<HTMLInputElement | HTMLSelectElement>(
      `input[name="${variable}"], select[name="${variable}"]`
    );
    if (inputElement) {
      variables[variable] = extractValue(inputElement);
      return variables[variable] !== '';
    }
    return false;
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
