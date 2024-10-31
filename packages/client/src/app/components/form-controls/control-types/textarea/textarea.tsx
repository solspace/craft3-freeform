import React, { useEffect, useImperativeHandle, useRef } from 'react';
import { Control } from '@components/form-controls/control';
import type { ControlType } from '@components/form-controls/types';
import type { TextareaProperty } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';

import { TextArea } from './textarea.styles';

const Textarea = React.forwardRef<
  HTMLTextAreaElement,
  ControlType<TextareaProperty>
>(
  (
    { value, property, errors, updateValue, autoFocus, focus, context },
    ref
  ) => {
    const { handle, rows } = property;
    const innerRef = useRef<HTMLTextAreaElement>(null);

    useImperativeHandle(ref, () => innerRef.current as HTMLTextAreaElement);

    useEffect(() => {
      if (focus) {
        innerRef.current?.focus();
      }
    }, [focus]);

    return (
      <Control property={property} errors={errors} context={context}>
        <TextArea
          id={handle}
          ref={innerRef}
          className={classes(
            'text',
            'fullwidth',
            property.flags?.includes('as-readonly-in-instance') && 'read-only',
            property.flags?.includes('code') && 'code'
          )}
          readOnly={property.flags?.includes('as-readonly-in-instance')}
          rows={rows}
          value={value ?? ''}
          placeholder={property.placeholder}
          autoFocus={autoFocus}
          onChange={(event) => updateValue(event.target.value)}
        />
      </Control>
    );
  }
);

Textarea.displayName = 'Textarea';

export default Textarea;
