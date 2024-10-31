import type { TypedUseSelectorHook } from 'react-redux';
import { useDispatch, useSelector, useStore } from 'react-redux';
import type { Action, AnyAction, Store } from '@reduxjs/toolkit';
import { configureStore } from '@reduxjs/toolkit';
import type { ThunkAction, ThunkDispatch } from 'redux-thunk';
import thunk from 'redux-thunk';

import { statePersistMiddleware } from './middleware/state-persist';
import context from './slices/context';
import form from './slices/form';
import integrations from './slices/integrations';
import layout from './slices/layout';
import notifications from './slices/notifications';
import rules from './slices/rules';
import search from './slices/search';
import translations from './slices/translations';

export const store = configureStore({
  middleware: [thunk, statePersistMiddleware],
  reducer: {
    form,
    layout,
    integrations,
    notifications,
    rules,
    context,
    search,
    translations,
  },
});

export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = ThunkDispatch<RootState, void, Action>;
export type AppThunk<R = void> = ThunkAction<R, RootState, unknown, AnyAction>;

export const useAppDispatch = (): AppDispatch => useDispatch<AppDispatch>();
export const useAppSelector: TypedUseSelectorHook<RootState> = useSelector;
export const useAppStore = (): Store<RootState> => useStore<RootState>();
