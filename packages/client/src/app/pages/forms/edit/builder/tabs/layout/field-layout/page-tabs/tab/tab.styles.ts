import { animated } from 'react-spring';
import { errorAlert } from '@ff-client/styles/mixins';
import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const TabWrapper = styled(animated.div)`
  position: relative;
`;

export const RemoveTabButton = styled.button`
  position: absolute;
  top: 3px;
  right: -8px;

  transition: all 0.2s ease-in-out;
  transform: scale(0.8);
  opacity: 0;

  &:active {
    outline: none;
  }

  &:hover {
    transform: scale(1);
  }

  svg {
    width: 20px;
  }
`;

export const TabText = styled.div`
  display: flex;
  align-items: center;
  gap: 10px;

  > span {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
  }
`;

export const PageTab = styled(animated.div)`
  display: flex;
  align-items: center;
  justify-content: center;

  max-width: 160px;
  height: 100%;
  padding: 7px 10px;
  margin: 0 5px;

  color: ${colors.gray400};
  border-bottom: 2px solid ${colors.gray100};

  overflow: hidden;

  &.active {
    color: ${colors.gray800};
    border-bottom-color: ${colors.blue600};
  }

  &.errors {
    color: ${colors.error};

    ${errorAlert};
  }

  &.can-drop {
    box-shadow: 0 2px 12px ${colors.gray500};
    transform: scale(1.1);
    z-index: 2;
  }

  &.is-dragging {
    z-index: 1;
  }

  &:hover {
    cursor: pointer;

    ${RemoveTabButton} {
      opacity: 1;
    }
  }
`;

export const TabDrop = styled.div`
  position: absolute;
  left: 0;
  top: 0;
  right: 0;
  bottom: 0;
  z-index: 2;

  width: 100%;
`;

export const Input = styled.input`
  appearance: none;

  display: block;
  width: 100%;
  min-width: 100px;

  border: 0;
  padding: 0 !important;
  line-height: 1rem;
  font-size: 0.75rem;
  box-shadow: none !important;

  &:hover,
  &:active {
    box-shadow: none !important;
  }

  &::-webkit-contacts-auto-fill-button {
    visibility: hidden;
    display: none !important;
    pointer-events: none;
    position: absolute;
    right: 0;
  }
`;

export const RemoveButtonWrapper = styled.div`
  position: absolute;
  top: 0px;
  right: -7px;

  transform: scale(0.8);
`;
