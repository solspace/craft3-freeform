import type { KeyboardEvent, MutableRefObject } from 'react';
import React, { useEffect, useRef, useState } from 'react';
import { useSelector } from 'react-redux';
import { RemoveButton } from '@components/elements/remove-button/remove';
import { TranslateIconWrapper } from '@components/form-controls/label.styles';
import type { Page } from '@editor/builder/types/layout';
import { useAppDispatch } from '@editor/store';
import { contextActions } from '@editor/store/slices/context';
import { contextSelectors } from '@editor/store/slices/context/context.selectors';
import { pageActions } from '@editor/store/slices/layout/pages';
import { pageSelecors } from '@editor/store/slices/layout/pages/pages.selectors';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import { deletePage } from '@editor/store/thunks/pages';
import { useClickOutside } from '@ff-client/hooks/use-click-outside';
import { useHover } from '@ff-client/hooks/use-hover';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';
import TranslateIcon from '@ff-icons/translate.icon.svg';

import { useDragContext } from '../../../drag.context';

import { useTabDrag } from './tab.drag';
import { useTabDragAnimation } from './tab.drag-animation';
import { useTabDrop, useTabPageDrop } from './tab.drop';
import {
  Input,
  PageTab,
  RemoveButtonWrapper,
  TabDrop,
  TabText,
  TabWrapper,
} from './tab.styles';

type Props = {
  page: Page;
  index: number;
};

export const Tab: React.FC<Props> = ({ page, index }) => {
  const currentPage = useSelector(contextSelectors.currentPage);
  const totalPages = useSelector(pageSelecors.count);

  const dispatch = useAppDispatch();
  const { dragType } = useDragContext();
  const {
    willTranslate,
    updateTranslation,
    getTranslation,
    hasTranslation,
    removeTranslation,
  } = useTranslations(page);

  const pageHasErrors = useSelector(contextSelectors.hasErrors(page.uid));

  const wrapperRef = useRef<HTMLDivElement>(null);
  const inputRef = useRef<HTMLInputElement>(null);

  const [isEditing, setIsEditing] = useState<boolean>(false);
  const isHovering = useHover(wrapperRef);

  const { canDrop, ref: dropRef } = useTabDrop(currentPage?.uid, page);
  const { isDragging, ref: dragRef } = useTabDrag(index, page);

  const { ref: dropPageRef, dragItemIndex } = useTabPageDrop(
    wrapperRef,
    page,
    index
  );

  useEffect(() => {
    if (isEditing) {
      inputRef.current?.focus();
      inputRef.current?.select();
    }
  }, [isEditing]);

  const connectedRef = dropRef(
    dragRef(wrapperRef)
  ) as unknown as MutableRefObject<HTMLDivElement>;

  const style = useTabDragAnimation(
    wrapperRef,
    index,
    dragItemIndex,
    isDragging
  );

  const persistInputChanges = (): void => {
    const newLabel = inputRef.current.value || page.label;
    if (!updateTranslation('label', newLabel)) {
      dispatch(
        pageActions.updateLabel({
          uid: page.uid,
          label: newLabel,
        })
      );
    }
  };

  const handleKeyboardEvent = (
    event: KeyboardEvent<HTMLInputElement>
  ): void => {
    if (event.key === 'Enter') {
      persistInputChanges();
      setIsEditing(false);
    }

    if (event.key === 'Escape') {
      setIsEditing(false);
    }
  };

  const clickOutsideRef = useClickOutside<HTMLDivElement>({
    callback: (): void => {
      persistInputChanges();
      setIsEditing(false);
    },
    isEnabled: isEditing,
  });

  return (
    <TabWrapper ref={connectedRef} className="page-tab">
      {(!!dragType || isDragging) && <TabDrop ref={dropPageRef} />}
      <PageTab
        ref={clickOutsideRef}
        className={classes(
          currentPage?.uid === page.uid && 'active',
          pageHasErrors && 'errors',
          canDrop && 'can-drop',
          isEditing && 'is-editing',
          isDragging && 'is-dragging'
        )}
        style={style}
        onClick={(): void => {
          dispatch(contextActions.setPage(page.uid));
        }}
        onDoubleClick={(): void => setIsEditing(true)}
      >
        {isEditing ? (
          <Input
            type="text"
            ref={inputRef}
            className="text small"
            placeholder={page.label}
            defaultValue={getTranslation('label', page.label)}
            onKeyUp={handleKeyboardEvent}
          />
        ) : (
          <TabText>
            <span>{getTranslation('label', page.label)}</span>
            {willTranslate('label') && (
              <TranslateIconWrapper
                className={classes(hasTranslation('label') && 'active')}
                onClick={(): void => {
                  if (
                    hasTranslation('label') &&
                    confirm('Are you sure you want to remove the translation?')
                  ) {
                    removeTranslation('label');
                  }
                }}
              >
                <TranslateIcon />
              </TranslateIconWrapper>
            )}
          </TabText>
        )}

        {totalPages > 1 && (
          <RemoveButtonWrapper>
            <RemoveButton
              active={isHovering && !isEditing}
              onClick={() => {
                if (!confirm(translate('Are you sure?'))) {
                  return;
                }

                dispatch(deletePage(page));
              }}
            />
          </RemoveButtonWrapper>
        )}
      </PageTab>
    </TabWrapper>
  );
};
