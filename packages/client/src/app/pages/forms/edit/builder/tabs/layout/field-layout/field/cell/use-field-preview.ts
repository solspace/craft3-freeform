import { useMemo } from 'react';
import { useFieldOptions } from '@components/options/use-field-options';
import type { Field } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import type { PropertyValueCollection } from '@ff-client/types/fields';
import { type FieldType } from '@ff-client/types/fields';
import { template } from 'lodash';

export const useFieldPreview = (
  field?: Field,
  type?: FieldType
): [string, boolean] => {
  const [generatedOptions, isFetching] = useFieldOptions(field, type);
  const { getTranslation } = useTranslations(field);

  const data: PropertyValueCollection = {};

  Object.entries(field.properties).forEach(([key, value]) => {
    const typeProperty = type?.properties.find((p) => p.handle === key);
    if (typeProperty && typeProperty.translatable) {
      data[key] = getTranslation(key, value);
    } else {
      data[key] = value;
    }
  });

  data.generatedOptions = generatedOptions;

  const compiledTemplate = useMemo(() => {
    if (
      field?.properties === undefined ||
      type?.previewTemplate === undefined
    ) {
      return 'No preview available';
    }

    try {
      const compiled = template(type.previewTemplate);
      return compiled(data);
    } catch (error) {
      return `Preview template error: "${error.message}"`;
    }
  }, [field?.properties, type?.previewTemplate, generatedOptions, data]);

  return [compiledTemplate, isFetching];
};
