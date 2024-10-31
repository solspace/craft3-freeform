import React, { useEffect, useRef } from 'react';
import { Control } from '@components/form-controls/control';
import type { ControlType } from '@components/form-controls/types';
import type { StringProperty } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';

const String: React.FC<ControlType<StringProperty>> = ({
  value,
  property,
  errors,
  updateValue,
  autoFocus,
  context,
}) => {
  const { handle } = property;
  const ref = useRef<HTMLInputElement>(null);

  useEffect(() => {
    if (autoFocus) {
      ref.current?.focus({ preventScroll: true });
    }
  }, [autoFocus]);

  return (
    <Control property={property} errors={errors} context={context}>
      <input
        id={handle}
        ref={ref}
        type="text"
        autoComplete="off"
        data-1p-ignore
        className={classes(
          'text',
          'fullwidth',
          property?.flags?.includes('code') && 'code'
        )}
        value={value ?? ''}
        placeholder={property.placeholder}
        onChange={(event) => updateValue(event.target.value)}
      />
    </Control>
  );
};

export default String;
