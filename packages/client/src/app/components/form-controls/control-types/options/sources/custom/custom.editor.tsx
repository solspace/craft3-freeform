import React, { useEffect, useRef, useState } from 'react';
import { HelpText } from '@components/elements/help-text';
import Bool from '@components/form-controls/control-types/bool/bool';
import {
  Button,
  Cell,
  Input,
  TableContainer,
  TabularOptions,
} from '@components/form-controls/control-types/table/table.editor.styles';
import { DraggableRow } from '@components/form-controls/draggable-row';
import { useCellNavigation } from '@components/form-controls/hooks/use-cell-navigation';
import CrossIcon from '@components/form-controls/icons/cross.svg';
import MoveIcon from '@components/form-controls/icons/move.svg';
import { PreviewableComponent } from '@components/form-controls/preview/previewable-component';
import { PreviewEditor } from '@components/form-controls/preview/previewable-component.styles';
import { useDebounce } from '@ff-client/hooks/use-debounce';
import { PropertyType } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import type {
  ConfigurationProps,
  CustomOptionsConfiguration,
  Option,
} from '../../options.types';

import { Bulk } from './custom.bulk';
import { BulkButton, BulkWrapper, ChoiceWrapper } from './custom.editor.styles';
import {
  addOption,
  deleteOption,
  moveOption,
  setOptions,
  toggleUseCustomValues,
  updateOption,
} from './custom.operations';

export const CustomEditor: React.FC<
  ConfigurationProps<CustomOptionsConfiguration>
> = ({ value, updateValue, defaultValue, updateDefaultValue, isMultiple }) => {
  const [localValue, setLocalValue] = useState(value);
  const debouncedValue = useDebounce(localValue, 500);

  useEffect(() => {
    updateValue(debouncedValue);
  }, [debouncedValue]);

  useEffect(() => {
    if (!localValue.options.length) {
      setLocalValue(addOption(localValue, 0));
    }
  }, [localValue]);

  const { options = [], useCustomValues = false } = localValue;

  const refs = useRef([]);
  refs.current = options.map(
    (option, index) =>
      refs.current[index] || React.createRef<HTMLButtonElement>()
  );

  const { activeCell, setActiveCell, setCellRef, keyPressHandler } =
    useCellNavigation(options.length, useCustomValues ? 2 : 1);

  const addCell = (cellIndex: number, atIndex?: number): void => {
    setActiveCell(
      atIndex !== undefined ? atIndex + 1 : options.length,
      cellIndex
    );
    setLocalValue(
      addOption(
        localValue,
        atIndex === undefined ? options.length : atIndex + 1
      )
    );
  };

  const bulkImport = (
    values: string,
    separator: string,
    append: boolean
  ): void => {
    let currentOptions: Option[] = [];
    if (append) {
      if (options[0] && options[0].label === '' && options[0].value === '') {
        currentOptions = [];
      } else {
        currentOptions = [...options];
      }
    }

    values.split('\n').forEach((line) => {
      let [label, value] = line.split(separator);
      label = label.trim();
      value = value?.trim();

      if (!label && !value) {
        return;
      }

      currentOptions.push({
        label: label,
        value: useCustomValues && !!value ? value : label,
      });
    });

    setLocalValue(setOptions(localValue, currentOptions));
  };

  return (
    <PreviewEditor>
      <ChoiceWrapper>
        <Bool
          property={{
            label: translate('Use custom values'),
            handle: 'useCustomValues',
            type: PropertyType.Boolean,
          }}
          value={useCustomValues}
          updateValue={() =>
            setLocalValue(toggleUseCustomValues(localValue, !useCustomValues))
          }
        />

        <BulkWrapper>
          <PreviewableComponent
            preview={
              <BulkButton>
                <i className="fa-duotone fa-list" />
                <span>{translate('Add options in bulk')}</span>
              </BulkButton>
            }
          >
            {(isEditing, close) => (
              <Bulk open={isEditing} close={close} bulkImport={bulkImport} />
            )}
          </PreviewableComponent>
        </BulkWrapper>
      </ChoiceWrapper>
      {!!options.length && (
        <TableContainer>
          <TabularOptions>
            <tbody>
              {options.map((option, index) => (
                <DraggableRow
                  key={index}
                  index={index}
                  dragRef={refs.current[index]}
                  onDrop={(fromIndex, toIndex) =>
                    setLocalValue(moveOption(localValue, fromIndex, toIndex))
                  }
                >
                  <Cell>
                    <Input
                      type="text"
                      value={option.label}
                      placeholder={translate('Label')}
                      autoFocus={activeCell === `${index}:0`}
                      ref={(element) => setCellRef(element, index, 0)}
                      onFocus={() => setActiveCell(index, 0)}
                      onKeyDown={keyPressHandler({
                        onEnter: ({ shiftKey }) => {
                          addCell(0, shiftKey ? index : undefined);
                        },
                      })}
                      onChange={(event) =>
                        setLocalValue(
                          updateOption(
                            index,
                            {
                              ...option,
                              label: event.target.value,
                              value: event.target.value,
                            },
                            localValue
                          )
                        )
                      }
                    />
                  </Cell>

                  {useCustomValues && (
                    <Cell>
                      <Input
                        type="text"
                        className="code"
                        value={option.value}
                        placeholder={translate('Value')}
                        autoFocus={activeCell === `${index}:1`}
                        ref={(element) => setCellRef(element, index, 1)}
                        onFocus={() => setActiveCell(index, 1)}
                        onKeyDown={keyPressHandler({
                          onEnter: ({ shiftKey }) => {
                            addCell(1, shiftKey ? index : undefined);
                          },
                        })}
                        onChange={(event) =>
                          setLocalValue(
                            updateOption(
                              index,
                              {
                                ...option,
                                value: event.target.value,
                              },
                              localValue
                            )
                          )
                        }
                      />
                    </Cell>
                  )}

                  {options.length > 1 && (
                    <>
                      <Cell $tiny>
                        <Bool
                          property={{
                            label: '',
                            handle: `${index}-check`,
                            type: PropertyType.Boolean,
                          }}
                          value={
                            isMultiple
                              ? defaultValue.includes(option.value)
                              : option.value === defaultValue
                          }
                          updateValue={() => {
                            if (isMultiple) {
                              const val = defaultValue as string[];

                              updateDefaultValue(
                                val.includes(option.value)
                                  ? val.filter(
                                      (value) => value !== option.value
                                    )
                                  : [...val, option.value]
                              );
                            } else {
                              updateDefaultValue(
                                option.value === defaultValue
                                  ? ''
                                  : option.value
                              );
                            }
                          }}
                        />
                      </Cell>
                      <Cell $tiny>
                        <Button ref={refs.current[index]} className="handle">
                          <MoveIcon />
                        </Button>
                      </Cell>
                      <Cell $tiny>
                        <Button
                          onClick={() => {
                            setLocalValue(deleteOption(index, localValue));
                            setActiveCell(Math.max(index - 1, 0), 0);
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
      )}

      <HelpText>
        <span
          dangerouslySetInnerHTML={{
            __html: translate(
              'Press <b>enter</b> while editing a cell to add a new row.'
            ),
          }}
        />
      </HelpText>
    </PreviewEditor>
  );
};
