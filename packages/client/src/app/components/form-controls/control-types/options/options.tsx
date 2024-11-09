import React from 'react';
import { ButtonGroup } from '@components/elements/button-group/button-group';
import { ControlWrapper } from '@components/form-controls/control.styles';
import { FormErrorList } from '@components/form-controls/error-list';
import { Label } from '@components/form-controls/label.styles';
import type { ControlType } from '@components/form-controls/types';
import { useFieldOptions } from '@components/options/use-field-options';
import config, { Edition } from '@config/freeform/freeform.config';
import { useAppDispatch } from '@editor/store';
import { type Field, fieldActions } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import { useFieldType } from '@ff-client/queries/field-types';
import type { OptionsProperty } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import { generateDefaultValue } from './sources/defaults';
import { SourceComponent } from './sources/source.component';
import { OptionsTranslatable } from './sources/translations/translations';
import type { Option } from './options.types';
import { Source } from './options.types';
import { sourceLabels } from './options.types';

const Options: React.FC<ControlType<OptionsProperty, Field>> = ({
  value,
  errors,
  property,
  updateValue,
  context,
}) => {
  const { source } = value;
  const defaultValue: string | string[] = context.properties.defaultValue;

  const fieldType = useFieldType(context.typeClass);
  const isMultiple = fieldType?.implements.includes('multiValue');

  const { willTranslate } = useTranslations(context);

  const [options] = useFieldOptions(context, fieldType);
  const dispatch = useAppDispatch();
  const updateDefaultValue = (value: string | string[]): void => {
    dispatch(
      fieldActions.edit({
        uid: context.uid,
        handle: 'defaultValue',
        value,
      })
    );
  };

  const convertToCustomValues = (): void =>
    updateValue({
      source: Source.Custom,
      useCustomValues: true,
      options: [...options] as Option[],
    });

  if (willTranslate(property.handle)) {
    return (
      <OptionsTranslatable property={property} value={value} field={context} />
    );
  }

  return (
    <>
      {config.editions.isAtLeast(Edition.Lite) && (
        <ControlWrapper $width={property.width}>
          <Label>{translate('Source')}</Label>
          <ButtonGroup
            options={sourceLabels}
            value={source}
            onClick={(selectedSource) => {
              if (selectedSource !== source) {
                updateValue(generateDefaultValue(selectedSource as Source));
              }
            }}
          />
        </ControlWrapper>
      )}

      <SourceComponent
        value={value}
        updateValue={updateValue}
        property={property}
        defaultValue={defaultValue}
        updateDefaultValue={updateDefaultValue}
        convertToCustomValues={convertToCustomValues}
        isMultiple={isMultiple}
      />

      <FormErrorList errors={errors} />
    </>
  );
};

export default Options;
