import React, { useRef, useState } from 'react';
import { FormErrorList } from '@components/form-controls/error-list';
import type { ControlType } from '@components/form-controls/types';
import classes from '@ff-client/utils/classes';

import EditIcon from './edit-icon.svg';
import { useEditButtonAnimations, useLabelAnimation } from './label.animations';
import { EditableLabelWrapper, EditButton, Label } from './label.styles';

const Int: React.FC<ControlType<string>> = ({
  value,
  property,
  errors,
  updateValue,
}) => {
  const [hover, setHover] = useState(false);
  const [edit, setEdit] = useState(false);
  const { handle } = property;

  const inputRef = useRef<HTMLInputElement>(null);

  const labelAnimation = useLabelAnimation(hover);
  const editButtonAnimation = useEditButtonAnimations(hover);

  return (
    <EditableLabelWrapper className={classes(errors?.length > 0 && 'errors')}>
      {edit && (
        <input
          id={handle}
          ref={inputRef}
          type="text"
          className="text fullwidth"
          value={value || ''}
          onChange={(event) => updateValue(event.target.value)}
          onBlur={() => setEdit(false)}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              setEdit(false);
            }
          }}
        />
      )}

      {!edit && (
        <Label
          style={labelAnimation}
          onClick={() => {
            setEdit(true);
            setHover(false);
            setTimeout(() => {
              inputRef.current?.focus();
            }, 3);
          }}
          onMouseEnter={() => setHover(true)}
          onMouseLeave={() => setHover(false)}
        >
          <span>
            <span>{value}</span>
            <EditButton style={editButtonAnimation}>
              <EditIcon />
            </EditButton>
          </span>
        </Label>
      )}

      <FormErrorList errors={errors} />
    </EditableLabelWrapper>
  );
};

export default Int;
