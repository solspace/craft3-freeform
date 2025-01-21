import React, { useCallback, useEffect, useRef, useState } from 'react';
import { useEditorAnimations } from '@components/form-controls/preview/previewable-component.animations';
import { useEscapeStack } from '@ff-client/contexts/escape/escape.context';
import { useClickOutside } from '@ff-client/hooks/use-click-outside';
import { useOnKeypress } from '@ff-client/hooks/use-on-keypress';
import type { OptionCollection } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import RemoveIcon from '@ff-icons/actions/delete.svg';

import {
  findLabelByValue,
  findValueByShadowIndex,
} from '../custom-dropdown/dropdown.operations';
import {
  CloseButton,
  ListWrapper,
  Search,
} from '../custom-dropdown/dropdown.styles';
import { PopUpPortal } from '../pop-up-portal';

import { useFilteredOptions } from './option-picker.operations';
import { Options } from './option-picker.options';
import {
  OptionPickerWrapper,
  OptionsRollout,
  Picker,
  PickerClose,
  PickerInput,
  PickerText,
} from './option-picker.styles';

export type OptionPickerProps = {
  loading?: boolean;
  placeholder?: string;
  options: OptionCollection;
  value?: string[];
  onChange?: (value: string[]) => void;
};

export const OptionPicker: React.FC<OptionPickerProps> = ({
  options,
  loading,
  value = [],
  onChange,
}) => {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [focusIndex, setFocusIndex] = useState(0);

  const searchRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const containerRef = useClickOutside<HTMLDivElement>({
    callback: () => setOpen(false),
    isEnabled: open,
    excludeClassNames: ['option-picker-rollout'],
  });

  const { editorAnimation } = useEditorAnimations({
    wrapper: containerRef.current,
    editor: dropdownRef.current,
    isEditing: open,
  });

  const toggleOpen = useCallback(() => {
    if (!loading) {
      setOpen(!open);
    }
  }, [loading, open]);

  const [filteredOptions, optionCount] = useFilteredOptions(
    value,
    options,
    query
  );

  useEscapeStack(() => setOpen(false), open);

  useOnKeypress(
    {
      meetsCondition: open,
      type: 'keydown',
      callback: (event) => {
        if (event.key === 'ArrowDown' && focusIndex < optionCount - 1) {
          setFocusIndex((prev) => prev + 1);
        }

        if (event.key === 'ArrowUp' && focusIndex > 0) {
          setFocusIndex((prev) => prev - 1);
        }
      },
    },
    [focusIndex, optionCount]
  );

  useOnKeypress(
    {
      meetsCondition: open,
      type: 'keyup',
      callback: (event) => {
        if (event.key === 'Enter') {
          const value = findValueByShadowIndex(filteredOptions, focusIndex);
          onChange?.(updateCurrentValue(value));
        }
      },
    },
    [filteredOptions, focusIndex]
  );

  useEffect(() => {
    if (loading && open) {
      setOpen(false);
    }
  }, [loading]);

  useEffect(() => {
    if (open) {
      searchRef.current?.focus();
      setFocusIndex(0);
    } else {
      setQuery('');
    }
  }, [open, query]);

  const updateCurrentValue = (selectedValue: string): string[] => {
    if (value.includes(selectedValue)) {
      return value.filter((val) => val !== selectedValue);
    }

    return [...value, selectedValue];
  };

  const onOptionClick = useCallback(
    (value: string) => {
      onChange?.(updateCurrentValue(value));
    },
    [onChange]
  );

  return (
    <OptionPickerWrapper
      ref={containerRef}
      className={classes(open && 'open')}
      onClick={toggleOpen}
    >
      <PickerInput onClick={() => setOpen(!open)}>
        {value.map((val) => {
          const label = findLabelByValue(options, val);

          return (
            <Picker key={val}>
              <PickerText>{label}</PickerText>
              <PickerClose
                onClick={(event) => {
                  event.stopPropagation();
                  onChange?.(value.filter((v) => v !== val));
                }}
              >
                <RemoveIcon />
              </PickerClose>
            </Picker>
          );
        })}
      </PickerInput>

      <PopUpPortal>
        {open && (
          <OptionsRollout
            className="option-picker-rollout"
            ref={dropdownRef}
            style={editorAnimation}
          >
            <CloseButton>
              <RemoveIcon />
            </CloseButton>

            <Search
              placeholder="Search..."
              ref={searchRef}
              value={query}
              onClick={(event) => event.stopPropagation()}
              onKeyDown={(event) => {
                if (['ArrowUp', 'ArrowDown'].includes(event.key)) {
                  event.preventDefault();
                }
              }}
              onChange={(event) => setQuery(event.target.value)}
            />

            <ListWrapper>
              <Options
                options={filteredOptions}
                focusIndex={focusIndex}
                query={query}
                value={value}
                onChange={onOptionClick}
              />
            </ListWrapper>
          </OptionsRollout>
        )}
      </PopUpPortal>
    </OptionPickerWrapper>
  );
};
