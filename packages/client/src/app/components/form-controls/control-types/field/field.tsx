import React from 'react';
import { useSelector } from 'react-redux';
import { Dropdown } from '@components/elements/custom-dropdown/dropdown';
import { Control } from '@components/form-controls/control';
import type { ControlType } from '@components/form-controls/types';
import { fieldSelectors } from '@editor/store/slices/layout/fields/fields.selectors';
import { useFieldTypeSearch } from '@ff-client/queries/field-types';
import { type FieldProperty } from '@ff-client/types/properties';

const Field: React.FC<ControlType<FieldProperty>> = ({
  value,
  property,
  errors,
  updateValue,
}) => {
  const fields = useSelector(fieldSelectors.all);
  const findType = useFieldTypeSearch();

  const options = fields
    .filter((field) => {
      if (!property.implements) {
        return true;
      }

      const type = findType(field.typeClass);
      if (!type) {
        return false;
      }

      return property.implements.some((implementation) =>
        type.implements?.includes(implementation)
      );
    })
    .map((field) => ({
      value: field.uid,
      label: field.properties.label,
    }));

  return (
    <Control property={property} errors={errors}>
      <Dropdown
        onChange={updateValue}
        value={value}
        options={options}
        emptyOption={property.emptyOption}
      />
    </Control>
  );
};

export default Field;
