import type { FreeformEventParameters } from 'types/form';

export const dispatchCustomEvent = <T extends object = Record<string, never>>(
  name: string,
  parameters?: FreeformEventParameters<T>,
  element?: HTMLElement | Array<HTMLElement>
): Event & T => {
  const { bubbles = false, cancelable = true, ...eventParameters } = parameters || {};

  const event = createNewEvent(name, bubbles, cancelable);
  Object.assign(event, eventParameters);

  if (element) {
    if (element instanceof HTMLElement) {
      element.dispatchEvent(event);
    } else {
      Array.from(element).forEach((el) => el.dispatchEvent(event));
    }
  }

  return event as Event & T;
};

export const createNewEvent = (eventName: string, bubbles = true, cancelable = true): Event => {
  return new Event(eventName, { bubbles, cancelable });
};

type Options = {
  elements: Document | HTMLElement | Array<HTMLElement>;
  type: Array<keyof HTMLElementEventMap> | keyof HTMLElementEventMap | string | string[];
  callback: (this: HTMLElement, ev: Event) => void;
  options?: AddEventListenerOptions | boolean;
};

type Handler = (method: 'add' | 'remove', options: Options) => void;

type BatchListeners = (
  elements: Document | HTMLElement | Array<HTMLElement>,
  type: Array<keyof HTMLElementEventMap> | keyof HTMLElementEventMap | string | string[],
  callback: (this: HTMLElement, ev: Event) => void,
  options?: AddEventListenerOptions | boolean
) => void;

export const addListeners: BatchListeners = (elements, type, callback, options) => {
  handleListeners('add', { elements, type, callback, options });
};

export const removeListeners: BatchListeners = (elements, type, callback, options) => {
  handleListeners('remove', { elements, type, callback, options });
};

const handleListeners: Handler = (method, { type, elements, callback, options }) => {
  const typeArray = Array.isArray(type) ? type : [type];
  const elementArray = Array.isArray(elements) ? elements : [elements];

  Array.from(elementArray).forEach((element) => {
    typeArray.forEach((type) => {
      if (method === 'add') {
        element.addEventListener(type, callback, options);
      } else {
        element.removeEventListener(type, callback, options);
      }
    });
  });
};
