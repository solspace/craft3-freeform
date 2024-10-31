import React, { memo, useState } from 'react';
import { DragPreviewImage } from 'react-dnd';
import { RemoveButton } from '@components/elements/remove-button/remove';
import { useAppDispatch } from '@editor/store';
import { contextActions } from '@editor/store/slices/context';
import type { Field as FieldPropType } from '@editor/store/slices/layout/fields';
import { fieldThunks } from '@editor/store/thunks/fields';

import { FieldCell } from './cell/cell';
import { useFieldDragAnimation } from './field.animations';
import { useFieldDrag } from './field.drag';
import { createPreview } from './field.drag.preview';
import { FieldWrapper } from './field.styles';

type Props = {
  field: FieldPropType;
  index: number;
  width?: number;
  isOver: boolean;
  isCurrentRow: boolean;
  isDraggingField: boolean;
  dragFieldIndex?: number;
  hoverPosition?: number;
};

export const Field: React.FC<Props> = memo(
  ({
    field,
    index,
    width,
    isOver,
    isCurrentRow,
    isDraggingField,
    dragFieldIndex,
    hoverPosition,
  }) => {
    const dispatch = useAppDispatch();
    const [hovering, setHovering] = useState(false);
    const { isDragging, drag, preview } = useFieldDrag(field, index);
    const style = useFieldDragAnimation({
      width,
      isDragging,
      isOver,
      isCurrentRow,
      isDraggingField,
      dragFieldIndex,
      index,
      hoverPosition,
    });

    return (
      <>
        <DragPreviewImage
          connect={preview}
          src={createPreview(field.properties?.label)}
        />
        <FieldWrapper
          onMouseEnter={() => setHovering(true)}
          onMouseLeave={() => setHovering(false)}
          ref={drag}
          style={style}
        >
          <RemoveButton
            active={hovering}
            onClick={() => {
              dispatch(contextActions.unfocus());
              dispatch(fieldThunks.remove(field));
            }}
          />
          <FieldCell field={field} />
        </FieldWrapper>
      </>
    );
  }
);

Field.displayName = 'Field';
