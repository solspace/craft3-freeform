import type { PayloadAction } from '@reduxjs/toolkit';
import { createSlice } from '@reduxjs/toolkit';

import './translations.persistence';

import type {
  RemoveProps,
  TranslationState,
  UpdateProps,
} from './translations.types';

const initialState: TranslationState = {};

export const translationSlice = createSlice({
  name: 'translations',
  initialState,
  reducers: {
    update: (state, { payload }: PayloadAction<UpdateProps>) => {
      const { siteId, type, namespace, handle, value } = payload;
      if (!state) {
        return {
          [siteId]: {
            [type]: {
              [namespace]: {
                [handle]: value,
              },
            },
          },
        };
      }

      if (state[siteId] === undefined) {
        state[siteId] = {
          fields: {},
          form: {},
          pages: {},
        };
      }

      if (!state[siteId][type] || typeof state[siteId][type] !== 'object') {
        state[siteId][type] = {};
      }

      if (state[siteId][type] === undefined) {
        state[siteId][type] = {};
      }

      if (!state[siteId][type][namespace]) {
        state[siteId][type][namespace] = {};
      }

      state[siteId][type][namespace][handle] = value;
    },
    remove: (state, { payload }: PayloadAction<RemoveProps>) => {
      const { siteId, type, namespace, handle } = payload;
      if (state[siteId] === undefined) {
        return;
      }

      if (state[siteId][type] === undefined) {
        return;
      }

      if (state[siteId][type][namespace] === undefined) {
        return;
      }

      delete state[siteId][type][namespace][handle];
    },
    init: (_, action: PayloadAction<TranslationState>) => {
      return action.payload;
    },
  },
});

const { actions } = translationSlice;
export { actions as translationActions };

export default translationSlice.reducer;
