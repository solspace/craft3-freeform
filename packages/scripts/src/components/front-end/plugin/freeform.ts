import 'core-js/features/array/for-each';
import 'core-js/features/array/includes';
import 'core-js/features/dom-collections/for-each';
import 'core-js/features/get-iterator';
import 'core-js/features/iterator/for-each';
import 'core-js/features/object/assign';

import events from '@lib/plugin/constants/event-types';
import { SuccessBehavior } from '@lib/plugin/constants/form';
import BackButtonHandler from '@lib/plugin/handlers/fields/back-button';
import CalculationHandler from '@lib/plugin/handlers/fields/calculations';
import DatePickerHandler from '@lib/plugin/handlers/fields/datepicker';
import DragAndDropHandler from '@lib/plugin/handlers/fields/drag-and-drop';
import InputMaskHandler from '@lib/plugin/handlers/fields/input-mask';
import RatingHandler from '@lib/plugin/handlers/fields/rating';
import SignatureHandler from '@lib/plugin/handlers/fields/signature';
import TableHandler from '@lib/plugin/handlers/fields/table';
import GoogleTagManager from '@lib/plugin/handlers/form/google-tag-manager';
import RuleHandler from '@lib/plugin/handlers/form/rules';
import SaveFormHandler from '@lib/plugin/handlers/form/save-form';
import { isSafari } from '@lib/plugin/helpers/browser-check';
import { getClassQuery } from '@lib/plugin/helpers/classes';
import { addClass, getClassArray, removeClass, removeElement } from '@lib/plugin/helpers/elements';
import { dispatchCustomEvent } from '@lib/plugin/helpers/event-handling';
import type { AxiosResponse } from 'axios';
import axios from 'axios';
import type { Callback } from 'types/events';
import { type FreeformResponse } from 'types/events';
import type { FreeformEventParameters, FreeformHandler, FreeformHandlerConstructor, FreeformOptions } from 'types/form';

export default class Freeform {
  static _BACK_BUTTON_NAME = 'form_previous_page_button';
  static instances = new WeakMap<HTMLFormElement, Freeform>();

  id: string;
  form: HTMLFormElement;
  options: FreeformOptions = {
    ajax: false,
    disableReset: false,
    disableSubmit: false,
    autoScroll: false,
    scrollToAnchor: false,
    scrollOffset: 0,
    scrollElement: window,
    showProcessingSpinner: false,
    showProcessingText: false,
    processingText: null,
    prevButtonName: 'form_previous_page_button',

    skipHtmlReload: false,

    successBannerMessage: 'Form has been submitted successfully!',
    errorBannerMessage: 'Sorry, there was an error submitting the form. Please try again.',

    errorClassBanner: 'freeform-form-errors',
    errorClassList: 'freeform-errors',
    errorClassField: 'freeform-has-errors',
    successClassBanner: 'freeform-form-success',

    removeMessages: null,
    renderSuccess: null,
    renderFormErrors: null,
    renderFieldErrors: null,
  };

  _initializedHandlers: FreeformHandler[] = [];
  _handlers: FreeformHandlerConstructor[] = [
    BackButtonHandler,
    RuleHandler,
    DatePickerHandler,
    CalculationHandler,
    InputMaskHandler,
    RatingHandler,
    SignatureHandler,
    TableHandler,
    GoogleTagManager,
    DragAndDropHandler,
    SaveFormHandler,
  ];

  _lastButtonPressed?: HTMLButtonElement;
  _lockList: Set<string> = new Set<string>();
  _disableList: Set<string> = new Set<string>();

  static getInstance = (form: HTMLFormElement): Freeform => Freeform.instances.get(form);

  constructor(form: HTMLFormElement) {
    if (Freeform.instances.get(form)) {
      return Freeform.instances.get(form);
    }

    this.id = form.dataset.id;
    this.form = form;

    this._setInstances();

    const options: FreeformOptions = {
      ajax: form.getAttribute('data-ajax') !== null,
      disableReset: form.getAttribute('data-disable-reset') !== null,
      scrollToAnchor: form.getAttribute('data-scroll-to-anchor') !== null,
      autoScroll: form.getAttribute('data-auto-scroll') !== null,
      disableSubmit: form.getAttribute('data-disable-submit') !== null,
      showProcessingSpinner: form.getAttribute('data-show-processing-spinner') !== null,
      showProcessingText: form.getAttribute('data-show-processing-text') !== null,
      processingText: form.getAttribute('data-processing-text'),
      successBannerMessage: form.getAttribute('data-success-message'),
      errorBannerMessage: form.getAttribute('data-error-message'),
      skipHtmlReload: form.getAttribute('data-skip-html-reload') !== null,
    };

    this.options = {
      ...this.options,
      ...options,
    };

    this.disableSubmit('init');

    const stateCheck = setInterval(async () => {
      if (document.readyState === 'complete') {
        clearInterval(stateCheck);

        const readyEvent = this._dispatchEvent(events.form.ready, { options: {} });

        this.options = {
          ...this.options,
          ...readyEvent.options,
        };

        this._setUp();
        this._initHandlers();

        this.enableSubmit('init');

        const { scrollToAnchor } = this.options;
        if (scrollToAnchor) {
          this._scrollToForm();
        }
      }
    }, 50);
  }

  _scrollToForm = (): void => {
    const { scrollOffset, scrollElement } = this.options;
    const y = this.form.getBoundingClientRect().top + window.scrollY + scrollOffset;
    scrollElement.scrollTo({ top: y, behavior: this._isReducedMotion() ? 'instant' : 'smooth' });
  };

  _isReducedMotion = (): boolean => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

    return !mediaQuery || mediaQuery.matches;
  };

  _setUp = (): void => {
    this._attachListeners();

    const submitButtons = this._getSubmitButtons();
    submitButtons.forEach((button) => {
      button.dataset.originalText = button.innerText;
      button.dataset.processingText = this.options.processingText;
    });
  };

  _initHandlers = () => {
    this._handlers.forEach((handler) => {
      this._initializedHandlers.push(new handler(this));
    });
  };

  _resetHandlers = (): void => {
    this._initializedHandlers.forEach((handler) => (handler.reload ? handler.reload() : null));
  };

  has = (attribute: string): boolean => {
    return this.form.getAttribute(attribute) !== null;
  };

  setOption = <K extends keyof FreeformOptions>(name: K, value: FreeformOptions[K]) => {
    this.options[name] = value;
  };

  disableForm = (): void => {
    this.form.dataset.freeformDisabled = '';
  };

  enableForm = (): void => {
    delete this.form.dataset.freeformDisabled;
  };

  disableSubmit = (id: string = 'freeform') => {
    this._disableList.add(id);

    const submitButtons = Array.from(this._getSubmitButtons());
    for (const submit of submitButtons) {
      submit.disabled = true;
    }
  };

  enableSubmit = (id: string = 'freeform') => {
    this._disableList.delete(id);

    if (this._disableList.size > 0) {
      return;
    }

    const submitButtons = Array.from(this._getSubmitButtons());
    for (const submit of submitButtons) {
      submit.disabled = false;
    }
  };

  lockSubmit = (id: string = 'freeform') => {
    this._lockList.add(id);

    // Perform the actual lock only initially
    if (this._lockList.size > 1) {
      return;
    }

    const { disableSubmit, showProcessingSpinner, showProcessingText } = this.options;

    const submitButtons = this._getSubmitButtons();
    for (let i = 0; i < submitButtons.length; i++) {
      const submit = submitButtons[i];

      if (disableSubmit) {
        submit.disabled = true;
      }
    }

    let lastButton: HTMLButtonElement | undefined = this._lastButtonPressed;
    if (!lastButton) {
      lastButton = (this._getSubmitButtons()[0] as HTMLButtonElement) || undefined;
    }

    if (lastButton) {
      if (showProcessingSpinner) {
        lastButton.classList.add('freeform-processing');
      }

      if (showProcessingText) {
        lastButton.innerText = lastButton.dataset.processingText;
      }
    }
  };

  unlockSubmit = (id: string = 'freeform'): void => {
    this._lockList.delete(id);

    if (this._lockList.size > 0) {
      return;
    }

    this._unlockSubmitButtons();
  };

  forceUnlockSubmit = (): void => {
    this._lockList.clear();
    this._unlockSubmitButtons();
  };

  triggerResubmit = (): void => {
    this.unlockSubmit();

    if (this._lastButtonPressed) {
      this._lastButtonPressed.click();
    } else {
      this.triggerSubmit();
    }
  };

  triggerSubmit = (): void => {
    this.unlockSubmit();

    const submitButtons = this._getSubmitButtons();
    if (submitButtons.length) {
      submitButtons[0].click();
    }
  };

  _unlockSubmitButtons = (): void => {
    const { disableSubmit, showProcessingSpinner, showProcessingText } = this.options;

    const submitButtons = this._getSubmitButtons();
    for (let i = 0; i < submitButtons.length; i++) {
      const submit = submitButtons[i];

      if (disableSubmit) {
        submit.disabled = false;
      }

      if (showProcessingSpinner) {
        submit.classList.remove('freeform-processing');
      }

      if (showProcessingText) {
        submit.innerText = submit.dataset.originalText;
      }
    }
  };

  _setInstances = (): void => {
    const { form } = this;

    Freeform.instances.set(form, this);
    form.freeform = this;
  };

  /**
   * Attaches event listeners
   */
  _attachListeners = (): void => {
    const form = this.form;
    const actionInput = this.form.querySelector<HTMLInputElement>('input[name=freeform-action]');

    const actionButtons = form.querySelectorAll<HTMLButtonElement>('[data-freeform-action]');

    if (actionInput) {
      actionButtons.forEach((button) =>
        button.addEventListener('click', () => {
          this._lastButtonPressed = button;
          actionInput.value = button.getAttribute('data-freeform-action');
        })
      );

      // Reset the action-input after each submit
      form.addEventListener(events.form.ajaxAfterSubmit, () => {
        actionInput.value = 'submit';
      });
    }

    form.addEventListener('submit', this._onSubmit);

    const inputs = form.querySelectorAll<HTMLInputElement>('input, select, textarea');
    inputs.forEach((input) =>
      input.addEventListener('change', (event) => {
        this._removeMessageFrom(event.target as HTMLInputElement);
      })
    );
  };

  /**
   * Perform form submit
   */
  _onSubmit = async (event: SubmitEvent) => {
    this.lockSubmit();

    event.preventDefault();
    event.stopPropagation();

    const {
      options: { ajax },
    } = this;

    const pressedButton = event.submitter as HTMLButtonElement;
    let isBackButtonPressed = false;
    if (pressedButton && pressedButton.name && pressedButton.name === Freeform._BACK_BUTTON_NAME) {
      isBackButtonPressed = true;
    }

    const submitCallbacks: Record<number, Callback[]> = {};

    const onSubmitEvent = this._dispatchEvent(events.form.submit, {
      isBackButtonPressed,
      cancelable: true,
      addCallback: (callback: Callback, priority: number = 0): void => {
        if (submitCallbacks[priority] === undefined) {
          submitCallbacks[priority] = [];
        }

        submitCallbacks[priority].push(callback);
      },
    });

    if (onSubmitEvent.defaultPrevented) {
      this.forceUnlockSubmit();
      this._dispatchEvent(events.form.afterFailedSubmit, { cancelable: false });

      return false;
    }

    const sortedCallbacks = Object.entries(submitCallbacks)
      .sort(([priorityA], [priorityB]) => Number(priorityA) - Number(priorityB))
      .flatMap(([, callbackList]) => callbackList);

    for (const callback of sortedCallbacks) {
      const callbackResult = await callback();
      if (callbackResult === false) {
        this.forceUnlockSubmit();
        this._dispatchEvent(events.form.afterFailedSubmit, { cancelable: false });
        return false;
      }
    }

    if (ajax) {
      this._onSubmitAjax(event);

      return false;
    }

    this.form.submit();
  };

  /**
   * Removes all success and error messages
   */
  _removeMessages = (): void => {
    const event = this._dispatchEvent(events.form.removeMessages);
    if (event.defaultPrevented) {
      return;
    }

    if (typeof this.options.removeMessages === 'function') {
      this.options.removeMessages = this.options.removeMessages.bind(this);
      return this.options.removeMessages();
    }

    const { form, options } = this;
    const { successClassBanner, errorClassBanner, errorClassList, errorClassField } = options;

    // Remove any existing errors that are being shown
    removeElement(form.querySelectorAll(`.${getClassArray(errorClassList).join('.')}`));

    const fieldsWithErrors = form.querySelectorAll<HTMLInputElement>(`.${getClassArray(errorClassField).join('.')}`);
    fieldsWithErrors.forEach((field) => {
      this._removeMessageFrom(field);
    });

    // Remove success messages
    removeElement(form.querySelectorAll(getClassQuery(successClassBanner)));
    removeElement(document.querySelectorAll(getClassQuery(errorClassBanner)));
  };

  _removeMessageFrom = (field: HTMLInputElement): void => {
    const event = this._dispatchEvent(events.form.fieldRemoveMessages, { field });
    if (event.defaultPrevented) {
      return;
    }

    const { options } = this;
    const { errorClassList, errorClassField } = options;

    let errorContainerNode = field.parentNode;
    if (field.type) {
      if (field.type === 'radio' || (field.type === 'checkbox' && /\[]$/.test(field.name))) {
        errorContainerNode = field.parentNode.parentNode;
      }
    }

    removeElement(errorContainerNode.querySelector<HTMLElement>(getClassQuery(errorClassList)));

    const fields = errorContainerNode.querySelectorAll<HTMLInputElement>('input, select, textarea');
    for (let i = 0; i < fields.length; i++) {
      removeClass(fields[i], errorClassField);
    }
  };

  /**
   * Renders the successful form submit message
   *
   * @returns {*}
   * @private
   */
  _renderSuccessBanner = (): void => {
    const event = this._dispatchEvent(events.form.renderSuccess);
    if (event.defaultPrevented) {
      return;
    }

    if (typeof this.options.renderSuccess === 'function') {
      this.options.renderSuccess = this.options.renderSuccess.bind(this);
      return this.options.renderSuccess();
    }

    const { form, options } = this;
    const { successBannerMessage, successClassBanner } = options;

    const successMessage = document.createElement('div');
    addClass(successMessage, successClassBanner);

    const paragraph = document.createElement('p');
    paragraph.appendChild(document.createTextNode(successBannerMessage));

    successMessage.appendChild(paragraph);

    form.insertBefore(successMessage, form.childNodes[0]);
  };

  _renderFieldErrors = (errors: Record<string, string[]>) => {
    const event = this._dispatchEvent(events.form.renderFieldErrors, { errors });
    if (event.defaultPrevented) {
      return false;
    }

    if (typeof this.options.renderFieldErrors === 'function') {
      this.options.renderFieldErrors = this.options.renderFieldErrors.bind(this);
      return this.options.renderFieldErrors(errors);
    }

    const { form, options } = this;
    const { errorClassList, errorClassField } = options;

    for (const key in errors) {
      const messages = errors[key];
      const errorsList = document.createElement('ul');
      errorsList.setAttribute('data-field-errors', '');
      addClass(errorsList, errorClassList);

      for (let messageIndex = 0; messageIndex < messages.length; messageIndex++) {
        const message = messages[messageIndex];
        const listItem = document.createElement('li');
        listItem.appendChild(document.createTextNode(message));
        errorsList.appendChild(listItem);
      }

      const inputList = form.querySelectorAll(
        `
          [name="${key}"],
          [name="${key}[0][0]"],
          [type=file][name="${key}"],
          [type=file][name="${key}[]"],
          [data-error-append-target="${key}"]
        `
      );

      for (let inputIndex = 0; inputIndex < inputList.length; inputIndex++) {
        const input = inputList[inputIndex] as HTMLInputElement;

        if (input.dataset.errorAppendTarget !== undefined) {
          input.appendChild(errorsList);
        } else {
          addClass(input, errorClassField);
          input.parentElement.appendChild(errorsList);
        }
      }

      const groupInputList = form.querySelectorAll<HTMLInputElement>(
        `input[type=checkbox][name="${key}[]"], input[type=radio][name="${key}"]`
      );
      for (let inputIndex = 0; inputIndex < groupInputList.length; inputIndex++) {
        const input = groupInputList[inputIndex];

        addClass(input, errorClassField);
        input.parentElement.parentElement.appendChild(errorsList);
      }
    }
  };

  _renderFormErrors = (errors: string[]) => {
    const event = this._dispatchEvent(events.form.renderFormErrors, { errors });
    if (event.defaultPrevented) {
      return false;
    }

    if (typeof this.options.renderFormErrors === 'function') {
      this.options.renderFormErrors = this.options.renderFormErrors.bind(this);
      return this.options.renderFormErrors(errors);
    }

    const { form, options } = this;
    const { errorClassBanner, errorBannerMessage } = options;

    const errorBlock = document.createElement('div');
    addClass(errorBlock, errorClassBanner);

    const paragraph = document.createElement('p');
    paragraph.appendChild(document.createTextNode(errorBannerMessage));
    errorBlock.appendChild(paragraph);

    if (errors.length) {
      const errorsList = document.createElement('ul');
      for (let messageIndex = 0; messageIndex < errors.length; messageIndex++) {
        const message = errors[messageIndex];
        const listItem = document.createElement('li');

        listItem.appendChild(document.createTextNode(message));
        errorsList.appendChild(listItem);
      }

      errorBlock.appendChild(errorsList);
    }

    form.insertBefore(errorBlock, form.childNodes[0]);
  };

  _prepareFormData = () => {
    const { form } = this;

    const data = new FormData(form);

    // Safari hack - remove empty file upload inputs
    // Otherwise an ajax call with empty file uploads causes immense lag
    if (isSafari()) {
      for (let i = 0; i < form.elements.length; i++) {
        const element = form.elements[i] as HTMLInputElement;

        if (element.type === 'file') {
          if (element.value === '') {
            data.delete(element.name);
          }
        }
      }
    }

    return data;
  };

  quickSave = async (secret: string, token?: string): Promise<string | false | undefined> => {
    const { form } = this;
    const data = this._prepareFormData();
    data.set('action', 'freeform/submit/quick-save');
    data.set('storage-secret', secret);
    if (token) {
      data.set('token', token);
    }

    let request: AxiosResponse<FreeformResponse & { storageToken: string }>;
    try {
      request = await axios<FreeformResponse & { storageToken: string }>({
        method: form.getAttribute('method'),
        url: form.getAttribute('action') || window.location.href,
        data,
        headers: {
          'Cache-Control': 'no-cache',
          'X-Requested-With': 'XMLHttpRequest',
          HTTP_X_REQUESTED_WITH: 'XMLHttpRequest',
        },
      });
    } catch (error) {
      if (error?.response?.status === 417) {
        this.unlockSubmit();

        return false;
      }
    }

    this._removeMessages();

    const response = request.data;

    if (request.status === 200) {
      const { success, errors, formErrors, storageToken } = response;

      if (success) {
        return storageToken;
      }

      if (errors || formErrors) {
        this._dispatchEvent(events.form.ajaxError, { request, response, errors, formErrors });
        this._dispatchEvent(events.form.afterFailedSubmit, { cancelable: false });
        this._renderFieldErrors(errors);
        this._renderFormErrors(formErrors);
      }

      if (this.options.autoScroll) {
        this._scrollToForm();
      }
    } else {
      this._dispatchEvent(events.form.ajaxError, { request, response });
      this._dispatchEvent(events.form.afterFailedSubmit, { cancelable: false });
    }

    this.unlockSubmit();

    return;
  };

  _onSubmitAjax = (event: SubmitEvent) => {
    const { form } = this;

    const data = this._prepareFormData();
    const request = new XMLHttpRequest();

    const submitter = event.submitter as HTMLButtonElement | undefined;
    if (submitter && submitter.name) {
      data.append(submitter.name, '1');
    }

    const method = form.getAttribute('method');
    const action = form.getAttribute('action');

    request.open(method, action ? action : window.location.href, true);
    request.setRequestHeader('Cache-Control', 'no-cache');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.setRequestHeader('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
    request.onload = () => {
      this._removeMessages();

      if (request.status === 200) {
        const response = JSON.parse(request.response);
        const { success, finished, actions = [], errors, formErrors, returnUrl } = response;

        const onBeforeSuccess = this._dispatchEvent(events.form.ajaxBeforeSuccess, { request, response });
        if (onBeforeSuccess.defaultPrevented) {
          return;
        }

        if (!actions.length) {
          if (success) {
            if (finished && response.onSuccess === SuccessBehavior.RedirectReturnUrl && returnUrl) {
              const redirectEvent = this._dispatchEvent(events.form.ajaxSuccess, { request, response });

              if (redirectEvent.defaultPrevented) {
                return;
              }

              window.location.href = returnUrl;
              return;
            }

            if (response.html !== null && !this.options.skipHtmlReload) {
              form.innerHTML = response.html.replace(/<form[^>]*>/, '').replace('</form>', '');
            }

            if (!this.options.skipHtmlReload) {
              this._resetHandlers();
              this._setUp();
            }

            if (finished) {
              if (!this.options.disableReset) {
                // Reset the form so that the user may enter fresh information
                // if a submission is not being edited
                form.reset();
                this._dispatchEvent(events.form.reset);
              }

              if (response.onSuccess === SuccessBehavior.Reload) {
                this._renderSuccessBanner();
              }
            }

            this._dispatchEvent(events.form.ajaxSuccess, { request, response });
          } else if (errors || formErrors) {
            this._dispatchEvent(events.form.ajaxError, { request, response, errors, formErrors });
            this._dispatchEvent(events.form.afterFailedSubmit, { cancelable: false });
            this._renderFieldErrors(errors);
            this._renderFormErrors(formErrors);
          }
        } else {
          this._dispatchEvent(events.form.handleActions, { response, actions, cancelable: false });
        }

        const payload = response?.freeform_payload;
        if (payload) {
          const payloadInput = form.querySelector<HTMLInputElement>('input[name^=freeform_payload]');
          if (payloadInput) {
            payloadInput.value = payload;
          }
        }

        this._dispatchEvent(events.form.ajaxAfterSubmit, {
          data,
          request,
          response,
          cancelable: false,
        });

        if (this.options.autoScroll) {
          this._scrollToForm();
        }
      } else {
        const response = request.response;

        this._dispatchEvent(events.form.ajaxError, { request, response });
      }

      this.unlockSubmit();
    };

    const submitEvent = this._dispatchEvent(events.form.ajaxBeforeSubmit, { data, request });
    if (submitEvent.defaultPrevented) {
      return;
    }

    request.send(data);
  };

  _getSubmitButtons = (): NodeListOf<HTMLButtonElement | HTMLInputElement> => {
    const buttons = this.form.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
      `*[type=submit][data-freeform-action]`
    );

    if (buttons.length) {
      return buttons;
    }

    // Fallback to any submit buttons if none have the `data-freeform-action` attribute
    return this.form.querySelectorAll(`*[type=submit]`);
  };

  _getBackButtons = (): NodeListOf<HTMLButtonElement | HTMLInputElement> => {
    return this.form.querySelectorAll<HTMLButtonElement | HTMLInputElement>(
      `*[type=submit][data-freeform-action="back"]`
    );
  };

  _dispatchEvent = <T extends object = Record<string, never>>(
    name: string,
    parameters?: FreeformEventParameters<T>,
    element?: HTMLElement
  ): Event & T => {
    const event = dispatchCustomEvent(
      name,
      {
        ...parameters,
        form: this.form,
        freeform: this,
      },
      element
    );

    document.dispatchEvent(event);
    this.form.dispatchEvent(event);

    return event;
  };
}

// Attach to all forms
const forms = document.querySelectorAll<HTMLFormElement>('form[data-freeform]');
forms.forEach((form) => {
  new Freeform(form);
});

const recursiveFreeformAttachment = (node: HTMLFormElement) => {
  if (node.nodeName === 'FORM' && node.dataset?.freeform !== undefined) {
    new Freeform(node);
  }

  node?.childNodes.forEach(recursiveFreeformAttachment);
};

// Add an observer which listens for new forms
const observer = new MutationObserver((mutations) => {
  mutations.forEach((mutation) => {
    if (mutation.type !== 'childList') {
      return;
    }

    mutation.addedNodes.forEach((node) => {
      recursiveFreeformAttachment(node as HTMLFormElement);
    });
  });
});

// Start the observer
observer.observe(document.body, { childList: true, subtree: true });
