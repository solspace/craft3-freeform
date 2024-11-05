import type { CustomOptionsConfiguration } from '@components/form-controls/control-types/options/options.types';
import {
  type OptionsConfiguration,
  Source,
} from '@components/form-controls/control-types/options/options.types';
import type { Field } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import type { FieldType } from '@ff-client/types/fields';
import { Implementation, Type } from '@ff-client/types/fields';
import {
  type OptionCollection,
  PropertyType,
} from '@ff-client/types/properties';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

type FieldOptions = (
  field?: Field,
  type?: FieldType
) => [OptionCollection, boolean];

export const useFieldOptions: FieldOptions = (field, type) => {
  const { getOptionTranslations } = useTranslations(field);

  let optionsConfiguration: OptionsConfiguration | undefined;
  let emptyOption: string | undefined;

  if (type?.type === Type.OpinionScale) {
    optionsConfiguration = {
      source: Source.Custom,
      options: field.properties.scales.map((scale: [string, string]) => ({
        label: scale[1] || scale[0],
        value: scale[0],
      })),
      useCustomValues: true,
    };
  } else {
    if (type?.implements.includes(Implementation.GeneratedOptions)) {
      const optionsProperty = type?.properties.find(
        (property) => property.type === PropertyType.Options
      );

      if (optionsProperty) {
        const fieldValue = field?.properties[optionsProperty.handle];
        optionsConfiguration = getOptionTranslations(
          optionsProperty.handle,
          fieldValue
        );

        emptyOption = fieldValue?.emptyOption;
      }
    }
  }

  const isCustomOptions = optionsConfiguration?.source === Source.Custom;

  const { data, isFetching } = useQuery(
    ['field-options', optionsConfiguration],
    async () => {
      if (!optionsConfiguration || isCustomOptions) {
        return [];
      }

      if (
        optionsConfiguration?.source !== Source.Custom &&
        !optionsConfiguration.typeClass
      ) {
        return [];
      }

      try {
        const response = await axios.post<OptionCollection>(
          'api/options',
          optionsConfiguration
        );
        const { data } = response;

        return data;
      } catch (error) {
        console.error(error);
        return [];
      }
    },
    { staleTime: Infinity, cacheTime: Infinity, enabled: !isCustomOptions }
  );

  const isFetchingAsync =
    !!optionsConfiguration && !isCustomOptions && isFetching;

  let options = isCustomOptions
    ? (optionsConfiguration as CustomOptionsConfiguration).options
    : data || [];

  if (emptyOption) {
    options = [{ label: emptyOption, value: '' }, ...options];
  }

  return [options, isFetchingAsync];
};
