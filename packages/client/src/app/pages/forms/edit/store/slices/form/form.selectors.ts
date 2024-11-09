import type { RootState } from '@editor/store';
import type {
  Form,
  SettingCollection,
  SettingsNamespace,
} from '@ff-client/types/forms';
import type { GenericValue } from '@ff-client/types/properties';

import type { FormErrors } from './form.types';

export const formSelectors = {
  current: (state: RootState): Form | undefined => state.form,
  settings: {
    all:
      () =>
      (state: RootState): SettingCollection =>
        state.form.settings || {},
    one:
      (namespace: string) =>
      (state: RootState): SettingsNamespace =>
        state.form.settings?.[namespace],
    namespaces: {
      all:
        (namespace: string) =>
        (state: RootState): SettingsNamespace =>
          state.form.settings?.[namespace],
      one:
        (namespace: string, key: string) =>
        (state: RootState): GenericValue =>
          state.form.settings?.[namespace]?.[key],
    },
  },
  errors: (state: RootState): FormErrors => state.form.errors,
} as const;
