import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const Icon = styled.div`
  font-size: 10rem;
  margin: 0 0 1.5rem;

  svg {
    min-width: 160px;
    min-height: 160px;
  }

  &.fade {
    svg {
      fill: #a1a5aa;
      opacity: 0.5;
    }
  }
`;

export const Title = styled.h2`
  margin: 0;
  padding: 0;

  font-size: 1.5rem;
  color: ${colors.gray500};
`;

export const LiteTitle = styled.h2`
  margin: 0;
  padding: 0;

  font-size: 1.2rem;
  font-weight: normal;
  color: ${colors.gray500};
`;

export const Subtitle = styled.p`
  margin: 0;
  padding: 0;

  font-size: 1rem;
  color: ${colors.gray300};

  &:not(:last-child) {
    padding-bottom: 1.5rem;
  }
`;

export const EmptyBlockWrapper = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 5px;

  height: 100%;

  &.padded {
    padding: 3rem 1rem;
  }
`;
