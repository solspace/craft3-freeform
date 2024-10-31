import React from 'react';
import { useSelector } from 'react-redux';
import { FormComponent } from '@components/form-controls';
import { useAppDispatch } from '@editor/store';
import { useValueUpdateGenerator } from '@editor/store/hooks/value-update-generator';
import { type Field, fieldActions } from '@editor/store/slices/layout/fields';
import { fieldSelectors } from '@editor/store/slices/layout/fields/fields.selectors';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import { useFieldType } from '@ff-client/queries/field-types';
import type { Property } from '@ff-client/types/properties';

type Props = {
  property: Property;
  field: Field;
  autoFocus?: boolean;
};

export const FieldComponent: React.FC<Props> = ({
  property,
  field,
  autoFocus,
}) => {
  const dispatch = useAppDispatch();
  const type = useFieldType(field.typeClass);
  const { getTranslation, updateTranslation, canUseTranslationValue } =
    useTranslations(field);

  const fieldState = useSelector(fieldSelectors.one(field.uid));
  const context = {
    id: fieldState.id,
    ...(fieldState?.properties || {}),
  };

  const generateUpdateHandler = useValueUpdateGenerator(
    type.properties,
    context,
    (handle, value) => {
      if (!updateTranslation(handle, value)) {
        dispatch(
          fieldActions.edit({
            uid: field.uid,
            handle,
            value,
          })
        );
      }
    }
  );

  const value = field.properties?.[property.handle];
  const translationEnabledValue = getTranslation(property.handle, value);

  return (
    <>
      <FormComponent
        autoFocus={autoFocus}
        value={
          canUseTranslationValue(property) ? translationEnabledValue : value
        }
        property={property}
        updateValue={generateUpdateHandler(property)}
        errors={field.errors?.[property.handle]}
        context={field}
      />
    </>
  );
};
