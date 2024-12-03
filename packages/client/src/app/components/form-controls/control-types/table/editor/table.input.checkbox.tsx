import React from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import { generateRandomHash } from '@ff-client/utils/hash';
import translate from '@ff-client/utils/translations';

import { CheckboxContainer } from './table.editor.styles';
import type { TableEditorProps } from './table.editor.types';

export const TableCheckboxEditor: React.FC<TableEditorProps> = ({
  column,
  onUpdate,
}) => {
  const hash = `table-checkbox-${generateRandomHash(8)}`;
  const isChecked = column.checked ?? false;

  return (
    <CheckboxContainer>
      <Checkbox
        id={hash}
        checked={isChecked}
        onChange={() => onUpdate({ ...column, checked: !column.checked })}
      />
      <label htmlFor={hash}>
        {translate(isChecked ? 'checked by default' : 'unchecked by default')}
      </label>
    </CheckboxContainer>
  );
};
