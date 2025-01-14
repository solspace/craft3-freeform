import type { ReactElement } from 'react';
import { useEffect } from 'react';
import { useRef } from 'react';
import { useState } from 'react';
import React from 'react';
import { PopUpPortal } from '@components/elements/pop-up-portal';
import { useEscapeStack } from '@ff-client/contexts/escape/escape.context';
import { useClickOutside } from '@ff-client/hooks/use-click-outside';
import classes from '@ff-client/utils/classes';

import { useZIndex } from '../context/z-index.context';

import { useEditorAnimations } from './previewable-component.animations';
import {
  EditableContentWrapper,
  PreviewContainer,
  PreviewWrapper,
} from './previewable-component.styles';

type Props = {
  preview: ReactElement;
  onEdit?: () => void;
  onAfterEdit?: () => void;
  excludeClassNames?: string[];
  children:
    | React.ReactNode
    | ((isEditing: boolean, close: () => void) => React.ReactNode);
};

export const PreviewableComponent: React.FC<Props> = ({
  preview,
  onEdit,
  onAfterEdit,
  excludeClassNames = [],
  children,
}) => {
  const [isEditing, setIsEditing] = useState(undefined);

  const wrapperRef = useRef<HTMLDivElement>(null);
  const editorRef = useRef<HTMLDivElement>(null);

  const { editorAnimation } = useEditorAnimations({
    wrapper: wrapperRef.current,
    editor: editorRef.current,
    isEditing,
  });

  useClickOutside<HTMLDivElement>({
    callback: () => {
      setIsEditing(false);
    },
    isEnabled: isEditing,
    refObject: editorRef,
    excludeClassNames: [
      'tagify__dropdown',
      'dropdown-rollout',
      ...excludeClassNames,
    ],
  });

  const close = (): void => {
    setIsEditing(false);
  };

  const zIndex = useZIndex();
  useEscapeStack(() => setIsEditing(false), !!isEditing);

  // Call after-edit callbacks when the editor is being closed
  useEffect(() => {
    if (isEditing === false) {
      onAfterEdit?.();
    }
  }, [isEditing]);

  return (
    <PreviewWrapper ref={wrapperRef}>
      <PopUpPortal>
        <EditableContentWrapper
          style={{
            zIndex,
            pointerEvents: isEditing ? 'initial' : 'none',
            ...editorAnimation,
          }}
          className={classes(isEditing && 'active', 'editable-content')}
          ref={editorRef}
        >
          {typeof children === 'function'
            ? children(isEditing, close)
            : children}
        </EditableContentWrapper>
      </PopUpPortal>

      <PreviewContainer
        onClick={() => {
          setIsEditing(true);
          onEdit?.();
        }}
      >
        {preview}
      </PreviewContainer>
    </PreviewWrapper>
  );
};
