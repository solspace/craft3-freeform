import React, { useRef } from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import { HelpText } from '@components/elements/help-text';
import type { UpdateValue } from '@components/form-controls';
import {
  Button,
  Cell,
  Input,
  Select,
  TableContainer,
  TableEditorWrapper,
  TabularOptions,
} from '@components/form-controls/control-types/table/table.editor.styles';
import {
  addColumn,
  deleteColumn,
  moveColumn,
  updateColumn,
} from '@components/form-controls/control-types/table/table.operations';
import type { ColumnDescription } from '@components/form-controls/control-types/table/table.types';
import { DraggableRow } from '@components/form-controls/draggable-row';
import { useCellNavigation } from '@components/form-controls/hooks/use-cell-navigation';
import CrossIcon from '@components/form-controls/icons/cross.svg';
import MoveIcon from '@components/form-controls/icons/move.svg';
import type { Option as PropertyOption } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import { TableCheckboxEditor } from './editor/table.input.checkbox';
import { TableDropdownEditor } from './editor/table.input.dropdown';
import { TableTextEditor } from './editor/table.input.text';

type Props = {
  columnTypes: PropertyOption[];
  columns: ColumnDescription[];
  updateValue: UpdateValue<ColumnDescription[]>;
};

export const TableEditor: React.FC<Props> = ({
  columnTypes,
  columns,
  updateValue,
}) => {
  const refs = useRef([]);
  refs.current = columns.map(
    (column, index) =>
      refs.current[index] || React.createRef<HTMLButtonElement>()
  );

  const { activeCell, setActiveCell, setCellRef, keyPressHandler } =
    useCellNavigation(columns.length, 2);

  const appendAndFocus = (cellIndex: number, atIndex?: number): void => {
    setActiveCell(
      atIndex !== undefined ? atIndex + 1 : columns.length,
      cellIndex
    );
    updateValue(
      addColumn(columns, atIndex !== undefined ? atIndex : columns.length)
    );
  };

  return (
    <TableEditorWrapper>
      <TableContainer>
        <TabularOptions>
          <tbody>
            {columns.map((column, rowIndex) => (
              <DraggableRow
                key={rowIndex}
                index={rowIndex}
                dragRef={refs.current[rowIndex]}
                onDrop={(fromIndex, toIndex) =>
                  updateValue(moveColumn(fromIndex, toIndex, columns))
                }
              >
                <Cell>
                  <Input
                    type="text"
                    value={column.label}
                    placeholder={translate('Label')}
                    autoFocus={activeCell === `${rowIndex}:0`}
                    ref={(element) => setCellRef(element, rowIndex, 0)}
                    onFocus={() => setActiveCell(rowIndex, 0)}
                    onKeyDown={keyPressHandler({
                      onEnter: (event) => {
                        appendAndFocus(
                          0,
                          event.shiftKey ? rowIndex : undefined
                        );
                      },
                    })}
                    onChange={(event) =>
                      updateValue(
                        updateColumn(
                          rowIndex,
                          { ...column, label: event.target.value },
                          columns
                        )
                      )
                    }
                  />
                </Cell>
                <Cell $width={110}>
                  <Select
                    defaultValue={column.type}
                    title={translate('Type')}
                    ref={(element) => setCellRef(element, rowIndex, 1)}
                    onFocus={() => setActiveCell(rowIndex, 1)}
                    onKeyDown={keyPressHandler({
                      onEnter: (event) => {
                        appendAndFocus(
                          0,
                          event.shiftKey ? rowIndex : undefined
                        );
                      },
                    })}
                    onChange={(event) =>
                      updateValue(
                        updateColumn(
                          rowIndex,
                          { ...column, type: event.target.value },
                          columns
                        )
                      )
                    }
                  >
                    {Object.values(columnTypes).map(({ value, label }) => (
                      <option key={value} value={value} label={label} />
                    ))}
                  </Select>
                </Cell>
                <Cell>
                  {renderCellEditor(column, (col: ColumnDescription) =>
                    updateValue(updateColumn(rowIndex, col, columns))
                  )}
                </Cell>
                <Cell title={translate('Mark this column as required?')} $tiny>
                  <Checkbox
                    checked={column.required}
                    onChange={() => {
                      updateValue(
                        updateColumn(
                          rowIndex,
                          { ...column, required: !column.required },
                          columns
                        )
                      );
                    }}
                  />
                </Cell>
                {columns.length > 1 && (
                  <>
                    <Cell $tiny>
                      <Button ref={refs.current[rowIndex]} className="handle">
                        <MoveIcon />
                      </Button>
                    </Cell>
                    <Cell $tiny>
                      <Button
                        onClick={() => {
                          updateValue(deleteColumn(rowIndex, columns));
                          setActiveCell(Math.max(rowIndex - 1, 0), 0);
                        }}
                      >
                        <CrossIcon />
                      </Button>
                    </Cell>
                  </>
                )}
              </DraggableRow>
            ))}
          </tbody>
        </TabularOptions>
      </TableContainer>

      <HelpText>
        <span
          dangerouslySetInnerHTML={{
            __html: translate(
              'Press <b>enter</b> while editing a cell to add a new row.'
            ),
          }}
        />
      </HelpText>
    </TableEditorWrapper>
  );
};

const renderCellEditor = (
  column: ColumnDescription,
  update: (col: ColumnDescription) => void
): React.ReactNode => {
  if (['text', 'textarea'].includes(column.type)) {
    return <TableTextEditor column={column} onUpdate={update} />;
  }

  if (['select', 'radio'].includes(column.type)) {
    return <TableDropdownEditor column={column} onUpdate={update} />;
  }

  if (column.type === 'checkbox') {
    return <TableCheckboxEditor column={column} onUpdate={update} />;
  }

  return null;
};
