import React from 'react';
import FormLabel from '@components/form-controls/label';
import { PreviewableComponent } from '@components/form-controls/preview/previewable-component';
import type { Field } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import type { Property } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import {
  NoContent,
  PreviewData,
  PreviewRow,
  PreviewTable,
  PreviewWrapper,
} from '../../../table/table.preview.styles';
import type { Option, OptionsConfiguration } from '../../options.types';

import { OptionsTranslationsEditor } from './translations.editor';
import type { OptionTranslations } from './translations.types';

type Props = {
  property: Property;
  value: OptionsConfiguration;
  field: Field;
};

export const OptionsTranslatable: React.FC<Props> = ({
  value,
  field,
  property,
}) => {
  const { hasTranslation, getTranslation, removeTranslation } =
    useTranslations(field);

  if (value.source !== 'custom') {
    return null;
  }

  const { options } = value;
  const { handle } = property;

  const translation = getTranslation<OptionTranslations>(handle, {});
  const optionTranslations: Option[] = translation.options || [];

  return (
    <>
      <FormLabel
        label="Options"
        handle={handle}
        translatable
        hasTranslation={hasTranslation(handle)}
        removeTranslation={() => removeTranslation(handle)}
      />
      <PreviewableComponent
        preview={
          <PreviewWrapper data-edit={translate('Click to edit data')}>
            <PreviewTable>
              {!options.length && (
                <NoContent>{translate('Not configured yet')}</NoContent>
              )}
              {options.map((option, index) => (
                <PreviewRow key={index}>
                  <PreviewData data-empty={translate('empty')}>
                    {optionTranslations.find(
                      (opt) => opt.value === option.value
                    )?.label || option.label}
                  </PreviewData>
                  <PreviewData className="code" data-empty={translate('empty')}>
                    {option.value}
                  </PreviewData>
                </PreviewRow>
              ))}
            </PreviewTable>
          </PreviewWrapper>
        }
        excludeClassNames={['bulk-editor']}
      >
        <OptionsTranslationsEditor
          value={value}
          field={field}
          property={property}
        />
      </PreviewableComponent>
    </>
  );
};
