import React from 'react';
import translate from '@ff-client/utils/translations';

import { Input } from '../table.editor.styles';

import { TextContainer } from './table.editor.styles';
import type { TableEditorProps } from './table.editor.types';

export const TableTextEditor: React.FC<TableEditorProps> = ({
  column,
  onUpdate,
}) => {
  return (
    <TextContainer>
      <Input
        value={column.value || ''}
        placeholder={translate('Value')}
        onChange={(event) => {
          onUpdate({ ...column, value: event.target.value });
        }}
      />

      <Input
        value={column.placeholder || ''}
        placeholder={translate('Placeholder')}
        onChange={(event) => {
          onUpdate({ ...column, placeholder: event.target.value });
        }}
      />
    </TextContainer>
  );
};
