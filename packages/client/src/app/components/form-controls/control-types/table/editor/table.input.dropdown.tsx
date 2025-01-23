import React, { useEffect } from 'react';

import { Input } from '../table.editor.styles';

import {
  AddButton,
  OptionContainer,
  RemoveButton,
} from './table.editor.styles';
import type { TableEditorProps } from './table.editor.types';

export const TableDropdownEditor: React.FC<TableEditorProps> = ({
  column,
  onUpdate,
}) => {
  useEffect(() => {
    if (!column?.options.length) {
      onUpdate({ ...column, options: [''] });
    }
  }, [column, onUpdate]);

  return (
    <div>
      {column?.options?.map((option, index) => (
        <OptionContainer key={index}>
          <input
            type="radio"
            checked={column.value === option}
            onChange={() => {
              onUpdate({ ...column, value: option });
            }}
          />

          <Input
            value={option}
            placeholder="Option"
            onChange={(event) => {
              const newOptions = [...column.options];
              newOptions[index] = event.target.value;
              onUpdate({ ...column, options: newOptions });
            }}
          />

          {index === column.options.length - 1 && (
            <AddButton
              onClick={() =>
                onUpdate({ ...column, options: [...column.options, ''] })
              }
            />
          )}

          {column.options.length > 1 && (
            <RemoveButton
              onClick={() => {
                const newOptions = [...column.options];
                newOptions.splice(index, 1);
                onUpdate({ ...column, options: newOptions });
              }}
            />
          )}
        </OptionContainer>
      ))}
    </div>
  );
};
