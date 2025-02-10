import React from 'react';
import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

const Overlay = styled.div`
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.2s ease-in;

  @keyframes fadeIn {
    from {
      opacity: 0;
    }
    to {
      opacity: 1;
    }
  }
`;

const Modal = styled.div`
  background: ${colors.white};
  padding: ${spacings.lg};
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  animation: scaleIn 0.2s ease-out;

  @keyframes scaleIn {
    from {
      transform: scale(0.95);
      opacity: 0;
    }
    to {
      transform: scale(1);
      opacity: 1;
    }
  }

  img {
    max-width: 100%;
    max-height: 80vh;
  }
`;

const CloseButton = styled.button`
  position: fixed;
  top: ${spacings.md};
  right: ${spacings.md};
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  animation: showButton 0.1s ease-out 0.3s forwards;

  @keyframes showButton {
    to {
      opacity: 0.8;
    }
  }

  &:hover {
    opacity: 1;
  }

  &::before,
  &::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 1px;
    background: ${colors.white};
  }

  &::before {
    transform: rotate(45deg);
  }

  &::after {
    transform: rotate(-45deg);
  }
`;

interface ScreenshotModalProps {
  imageUrl: string;
  testId: number;
  onClose: () => void;
}

export const ScreenshotModal: React.FC<ScreenshotModalProps> = ({
  imageUrl,
  testId,
  onClose,
}) => {
  return (
    <Overlay onClick={onClose}>
      <Modal onClick={(e) => e.stopPropagation()}>
        <CloseButton onClick={onClose} title="Close" />
        <img src={imageUrl} alt={`Test #${testId} screenshot`} />
      </Modal>
    </Overlay>
  );
};
