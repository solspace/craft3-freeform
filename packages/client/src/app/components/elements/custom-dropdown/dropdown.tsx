import React, {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useEditorAnimations } from '@components/form-controls/preview/previewable-component.animations';
import SpinnerIcon from '@components/loaders/spinner.svg';
import { useEscapeStack } from '@ff-client/contexts/escape/escape.context';
import { useClickOutside } from '@ff-client/hooks/use-click-outside';
import { useOnKeypress } from '@ff-client/hooks/use-on-keypress';
import type { OptionCollection } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import { PopUpPortal } from '../pop-up-portal';

import CloseIcon from './close.svg';
import {
  findLabelByValue,
  findShadowIndexByValue,
  findValueByShadowIndex,
  useFilteredOptions,
} from './dropdown.operations';
import { Options } from './dropdown.options';
import {
  CloseButton,
  CurrentValue,
  DropdownRollout,
  DropdownWrapper,
  ListWrapper,
  Search,
  SpinnerWrapper,
} from './dropdown.styles';

export type DropdownProps = {
  loading?: boolean;
  emptyOption?: string;
  options?: OptionCollection;
  value?: string;
  showValues?: boolean;
  onChange?: (value: string) => void;
};

export const Dropdown: React.FC<DropdownProps> = ({
  emptyOption,
  value,
  options,
  showValues,
  onChange,
  loading = false,
}) => {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [focusIndex, setFocusIndex] = useState(0);

  const searchRef = useRef<HTMLInputElement>(null);
  const dropdownRef = useRef<HTMLDivElement>(null);
  const containerRef = useClickOutside<HTMLDivElement>({
    callback: () => setOpen(false),
    isEnabled: open,
    excludeClassNames: ['dropdown-rollout'],
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

  if (emptyOption) {
    emptyOption = translate(emptyOption);
  }

  const [filteredOptions, optionCount] = useFilteredOptions(
    options,
    query,
    emptyOption
  );

  const selectedValue = useMemo(
    () => findLabelByValue(options, value) || emptyOption,
    [options, value, loading]
  );

  const selectedIndex = useMemo(
    () => findShadowIndexByValue(filteredOptions, value),
    [options, value, loading]
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
          onChange?.(value);
          setOpen(false);
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
      setFocusIndex(selectedIndex || 0);
    } else {
      setQuery('');
    }
  }, [open, query]);

  const onOptionClick = useCallback(
    (value: string) => {
      onChange?.(value);
      setOpen(false);
    },
    [onChange]
  );

  return (
    <DropdownWrapper
      ref={containerRef}
      className={classes(open && 'open')}
      onClick={toggleOpen}
    >
      <CurrentValue
        className={classes(loading && 'disabled', value === '' && 'empty')}
      >
        <span>{selectedValue}</span>

        {loading && (
          <SpinnerWrapper>
            <SpinnerIcon />
          </SpinnerWrapper>
        )}
      </CurrentValue>
      <PopUpPortal>
        {open && (
          <DropdownRollout
            className="dropdown-rollout"
            ref={dropdownRef}
            style={editorAnimation}
          >
            <CloseButton>
              <CloseIcon />
            </CloseButton>
            <Search
              placeholder={translate('Search...')}
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
                value={value}
                focusIndex={focusIndex}
                showValues={showValues}
                onChange={onOptionClick}
              />
            </ListWrapper>
          </DropdownRollout>
        )}
      </PopUpPortal>
    </DropdownWrapper>
  );
};
