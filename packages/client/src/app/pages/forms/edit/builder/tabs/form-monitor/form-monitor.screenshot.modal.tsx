import React from 'react';
import { colors, spacings } from '@ff-client/styles/variables';
import styled, { keyframes } from 'styled-components';

const fadeIn = keyframes`
  from { opacity: 0; }
  to { opacity: 1; }
`;

const scaleIn = keyframes`
  from { transform: scale(0.95); opacity: 0; }
  to { transform: scale(1); opacity: 1; }
`;

export const Overlay = styled.div`
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
  animation: ${fadeIn} 0.2s ease-in;
`;

export const ModalContent = styled.div`
  background: ${colors.white};
  padding: ${spacings.lg};
  border-radius: 8px;
  max-width: 90vw;
  max-height: 90vh;
  animation: ${scaleIn} 0.2s ease-out;

  img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 4px;
  }
`;

export const CloseButton = styled.button`
  position: fixed;
  top: ${spacings.md};
  right: ${spacings.md};
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;

  &:hover {
    background: rgba(255, 255, 255, 0.2);
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
      <ModalContent onClick={(e) => e.stopPropagation()}>
        <CloseButton onClick={onClose} aria-label="Close screenshot" />
        <img
          src={imageUrl}
          alt={`Screenshot for test #${testId}`}
          loading="lazy"
        />
      </ModalContent>
    </Overlay>
  );
};
