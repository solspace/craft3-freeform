import type Freeform from '@components/front-end/plugin/freeform';
import type { FreeformHandler } from 'types/form';

class InputMask implements FreeformHandler {
  freeform: Freeform;
  scriptAdded = false;

  constructor(freeform: Freeform) {
    this.freeform = freeform;

    if (!this.freeform.has('data-scripts-js-mask')) {
      return;
    }

    if (!this.scriptAdded) {
      const script = document.createElement('script');
      script.src = 'https://cdnjs.cloudflare.com/ajax/libs/imask/6.0.7/imask.min.js';
      script.async = false;
      script.defer = false;
      script.addEventListener('load', () => {
        this.reload();
      });
      document.body.appendChild(script);

      this.scriptAdded = true;
    }
  }

  reload = () => {
    if (!this.freeform.has('data-scripts-js-mask')) {
      return;
    }

    const maskedInputs = this.freeform.form.querySelectorAll('*[data-masked-input]');
    maskedInputs.forEach((input) => {
      const mask = input.getAttribute('data-pattern');
      if (mask) {
        // @ts-expect-error: IMask is not defined
        new IMask(input, { mask });
      }
    });
  };
}

export default InputMask;
