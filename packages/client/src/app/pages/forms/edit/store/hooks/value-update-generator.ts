import { useCallback } from 'react';
import { useSelector } from 'react-redux';
import type { MiddlewareInjectCallback } from '@components/middleware/middleware';
import { applyMiddleware } from '@components/middleware/middleware';
import { useSiteContext } from '@ff-client/contexts/site/site.context';
import type { PropertyValueCollection } from '@ff-client/types/fields';
import type { GenericValue, Property } from '@ff-client/types/properties';

import { formSelectors } from '../slices/form/form.selectors';

export type ValueUpdateHandler = <T>(value: T) => void;

type ValueUpdateHandlerGenerator = (property: Property) => ValueUpdateHandler;

export const useValueUpdateGenerator = (
  siblingProperties: Property[],
  state: PropertyValueCollection,
  updateValueCallback: (key: string, value: GenericValue) => void
): ValueUpdateHandlerGenerator => {
  const { isPrimary } = useSiteContext();
  const generalSettings = useSelector(formSelectors.settings.one('general'));
  const isTranslationsEnabled = generalSettings?.translations;

  return useCallback(
    (property) => {
      if (property.disabled) {
        return;
      }

      if (isTranslationsEnabled && !isPrimary) {
        return (value) => {
          updateValueCallback(property.handle, value);
        };
      }

      return (value) => {
        const injectCallback: MiddlewareInjectCallback = (key, value) => {
          const prop = siblingProperties.find((prop) => prop.handle === key);
          if (!prop || prop.disabled) {
            return;
          }

          updateValueCallback(
            prop.handle,
            applyMiddleware(value, prop.middleware, state)
          );
        };

        updateValueCallback(
          property.handle,
          applyMiddleware(value, property.middleware, state, injectCallback)
        );
      };
    },
    [
      siblingProperties,
      state,
      updateValueCallback,
      isPrimary,
      isTranslationsEnabled,
    ]
  );
};
