import type { FieldBase, FieldType } from '@ff-client/types/fields';
import { cloneDeep } from 'lodash';

export const cloneFieldTypeFromForm = (
  field: FieldBase,
  fieldType: FieldType
): FieldType => {
  const clone = cloneDeep(fieldType);

  Object.entries(field.properties).map(([name, value]) => {
    const property = clone?.properties?.find(
      (property) => property.handle === name
    );

    if (property) {
      property.value = value;
    }
  });

  return clone;
};
