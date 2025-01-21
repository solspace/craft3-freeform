import React, { useRef } from 'react';
import { useHover } from '@ff-client/hooks/use-hover';

import DeleteIcon from './delete.svg';
import { useRemoveAnimation } from './remove.animations';
import { RemoveButtonWrapper } from './remove.styles';

type Props = {
  active: boolean;
  onClick?: () => void;
};

export const RemoveButton: React.FC<Props> = ({ active, onClick }) => {
  const ref = useRef<HTMLButtonElement>(null);
  const hovering = useHover(ref);
  const animation = useRemoveAnimation({ active, hovering });

  return (
    <RemoveButtonWrapper
      ref={ref}
      style={animation}
      onClick={(event) => {
        event.stopPropagation();
        onClick?.();
      }}
    >
      <DeleteIcon />
    </RemoveButtonWrapper>
  );
};
