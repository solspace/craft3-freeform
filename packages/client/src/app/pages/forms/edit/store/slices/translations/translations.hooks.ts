import { useCallback } from 'react';
import { useSelector } from 'react-redux';
import type { OptionsConfiguration } from '@components/form-controls/control-types/options/options.types';
import type { OptionTranslations } from '@components/form-controls/control-types/options/sources/translations/translations.types';
import type { Page } from '@editor/builder/types/layout';
import { useAppDispatch, useAppSelector } from '@editor/store';
import { useSiteContext } from '@ff-client/contexts/site/site.context';
import { useFieldTypeSearch } from '@ff-client/queries/field-types';
import { useQueryFormSettings } from '@ff-client/queries/forms';
import { useFetchPageButtonType } from '@ff-client/queries/page-types';
import type { SettingsNamespace } from '@ff-client/types/forms';
import {
  type GenericValue,
  type Property,
  PropertyType,
} from '@ff-client/types/properties';
import { cloneDeep } from 'lodash';

import { formSelectors } from '../form/form.selectors';
import type { Field } from '../layout/fields';

import { translationSelectors } from './translations.selectors';
import type { TranslationType } from './translations.types';
import { translationActions } from '.';

type HasTranslation = (handle: string) => boolean;
type GetTranslation = <T = string>(handle: string, value: GenericValue) => T;
type GetOptionTranslations = (
  handle: string,
  value: OptionsConfiguration
) => OptionsConfiguration;
type UpdateTranslation = (handle: string, value: GenericValue) => boolean;
type RemoveTranslation = (handle: string) => void;
type WillTranslate = (handle: string) => boolean;
type CanUseTranslation = (property: Property) => boolean;

const excludedTranslationPropertyTypes = [PropertyType.Options];

type UseTranslations = {
  hasTranslation: HasTranslation;
  getTranslation: GetTranslation;
  getOptionTranslations: GetOptionTranslations;
  updateTranslation: UpdateTranslation;
  removeTranslation: RemoveTranslation;
  isTranslationsEnabled: boolean;
  willTranslate: WillTranslate;
  canUseTranslationValue: CanUseTranslation;
};

function useTranslations(field: Field): UseTranslations;
function useTranslations(form: SettingsNamespace): UseTranslations;
function useTranslations(page: Page): UseTranslations;
function useTranslations(
  target: Field | SettingsNamespace | Page
): UseTranslations {
  const dispatch = useAppDispatch();
  const { current, isPrimary } = useSiteContext();
  const searchType = useFieldTypeSearch();
  const generalSettings = useSelector(formSelectors.settings.one('general'));
  const isTranslationsEnabled = generalSettings?.translations;

  const { data: pageButtonType } = useFetchPageButtonType();
  const { data: formSettings } = useQueryFormSettings();

  const isField = target && 'typeClass' in target;
  const isForm =
    target && 'namespaceType' in target && target.namespaceType === 'settings';

  const siteId = current.id;
  const namespace = isForm ? target.namespace : target?.uid;
  const type: TranslationType = isField ? 'fields' : isForm ? 'form' : 'pages';

  const translationNamespace = useAppSelector(
    translationSelectors.namespace(current.id, target)
  );

  const findProperty = useCallback(
    (handle: string): Property => {
      if (isField) {
        const type = searchType(target.typeClass);
        if (!type) {
          return undefined;
        }

        return type.properties.find((prop) => prop.handle === handle);
      }

      if (isForm) {
        const setting = formSettings?.find(
          (setting) => setting.handle === namespace
        );

        if (!setting) {
          return undefined;
        }

        return setting.properties.find((prop) => prop.handle === handle);
      }

      return pageButtonType?.properties?.find((prop) => prop.handle === handle);
    },
    [isField, isForm, searchType, pageButtonType]
  );

  // ================
  //       HAS
  // ================
  const hasTranslation: HasTranslation = useCallback(
    (handle) => {
      return target && translationNamespace?.[handle] !== undefined;
    },
    [target, translationNamespace]
  );

  const willTranslate: WillTranslate = useCallback(
    (handle) => {
      if (!isTranslationsEnabled) {
        return false;
      }

      if (!target) {
        return false;
      }

      if (isPrimary) {
        return false;
      }

      const property = findProperty(handle);
      if (property === undefined) {
        if (handle === 'label') {
          return true;
        }

        return false;
      }

      return property.translatable;
    },
    [isPrimary, target, isTranslationsEnabled]
  );

  // ================
  //       GET
  // ================
  const getTranslation: GetTranslation = useCallback(
    (handle, value) => {
      if (!willTranslate(handle)) {
        return value;
      }

      if (!hasTranslation(handle)) {
        return value;
      }

      return translationNamespace[handle];
    },
    [target, translationNamespace]
  );

  const getOptionTranslations: GetTranslation = useCallback(
    (handle, value) => {
      if (!willTranslate(handle)) {
        return value;
      }

      if (!hasTranslation(handle)) {
        return value;
      }

      const translatedValue: OptionsConfiguration = cloneDeep(value);
      const translation: OptionTranslations = translationNamespace[handle];

      if (translatedValue.source === 'custom' && translation.options) {
        translatedValue.options = translatedValue.options.map((option) => {
          const translatedOption = translation.options.find(
            (opt) => opt.value === option.value
          );

          if (translatedOption) {
            return {
              ...option,
              label: translatedOption.label,
            };
          }

          return option;
        });
      }

      return translatedValue;
    },
    [target, translationNamespace]
  );

  // ================
  //      UPDATE
  // ================
  const updateTranslation: UpdateTranslation = (handle, value) => {
    if (!willTranslate(handle)) {
      return false;
    }

    dispatch(
      translationActions.update({
        siteId,
        type,
        namespace,
        handle,
        value,
      })
    );

    return true;
  };

  // ================
  //      REMOVE
  // ================
  const removeTranslation: RemoveTranslation = (handle) => {
    if (!willTranslate(handle)) {
      return;
    }

    dispatch(
      translationActions.remove({
        siteId,
        type,
        namespace,
        handle,
      })
    );
  };

  const canUseTranslationValue: CanUseTranslation = (property) => {
    return (
      property.translatable &&
      excludedTranslationPropertyTypes.includes(property.type) === false
    );
  };

  return {
    hasTranslation,
    willTranslate,
    getTranslation,
    getOptionTranslations,
    updateTranslation,
    removeTranslation,
    canUseTranslationValue,
    isTranslationsEnabled,
  };
}

export { useTranslations };
