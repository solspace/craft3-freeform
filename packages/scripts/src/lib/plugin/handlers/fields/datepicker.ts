import type Freeform from '@components/front-end/plugin/freeform';
import type { FreeformHandler } from 'types/form';

class DatePicker implements FreeformHandler {
  loadedLocales: Record<string, HTMLScriptElement> = {};
  freeform: Freeform;

  scriptAdded = false;

  constructor(freeform: Freeform) {
    this.freeform = freeform;

    if (!this.freeform.has('data-scripts-datepicker')) {
      return;
    }

    if (!this.scriptAdded) {
      const script = document.createElement('script');
      script.src = '//cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js';
      script.async = false;
      script.defer = false;
      script.addEventListener('load', () => {
        this.reload();
      });
      document.body.appendChild(script);

      const style = document.createElement('link');
      style.rel = 'stylesheet';
      style.href = '//cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css';
      document.body.appendChild(style);

      this.scriptAdded = true;
    }
  }

  reload = () => {
    if (!this.freeform.has('data-scripts-datepicker')) {
      return;
    }

    const pickers = this.freeform.form.querySelectorAll('*[data-datepicker][data-datepicker-enabled]');
    pickers.forEach((picker) => {
      const locale = picker.getAttribute('data-datepicker-locale');
      const options = {
        disableMobile: true,
        allowInput: true,
        dateFormat: picker.getAttribute('data-datepicker-format'),
        enableTime: picker.getAttribute('data-datepicker-enabletime') !== null,
        noCalendar: picker.getAttribute('data-datepicker-enabledate') === null,
        time_24hr: picker.getAttribute('data-datepicker-clock_24h') !== null,
        minDate: picker.getAttribute('data-datepicker-min-date'),
        maxDate: picker.getAttribute('data-datepicker-max-date'),
        minuteIncrement: 1,
        hourIncrement: 1,
        static: picker.getAttribute('data-datepicker-static') !== null,
      };

      const optionsEvent = this.freeform._dispatchEvent('flatpickr-before-init', { detail: options, options });
      const assembledOptions = {
        ...optionsEvent.detail,
        ...optionsEvent.options,
      };

      // @ts-expect-error: Flatpickr types are not included
      const instance = flatpickr(picker, assembledOptions);
      picker.setAttribute('autocomplete', 'off');

      this.freeform._dispatchEvent('flatpickr-ready', { detail: instance, flatpickr: instance });

      if (!this.loadedLocales[locale]) {
        const script = document.createElement('script');
        script.src = `//cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/${locale}.js`;
        script.async = false;
        script.defer = false;
        script.dataset.loaded = '';
        script.addEventListener('load', () => {
          instance.set('locale', locale);
          script.dataset.loaded = 'true';
        });
        document.body.appendChild(script);

        this.loadedLocales[locale] = script;
      } else {
        this.loadedLocales[locale].addEventListener('load', () => {
          instance.set('locale', locale);
          this.loadedLocales[locale].dataset.loaded = 'true';
        });

        if (this.loadedLocales[locale].dataset.loaded === 'true') {
          instance.set('locale', locale);
        }
      }
    });
  };
}

export default DatePicker;
