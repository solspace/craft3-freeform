import { spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const CardBody = styled.div`
  display: flex;
  justify-content: space-between;

  padding: ${spacings.xl} ${spacings.xl} 0;
`;

export const FormBody = styled.div`
  display: flex;
  flex-direction: column;
  justify-content: start;
`;

export const Title = styled.h2`
  cursor: default;

  margin: 0;

  color: #3d464e;

  font-size: 20px;
  font-weight: 700;
  text-align: left;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

  transition: all 0.2s ease-out;
`;

export const TitleLink = styled(Title)`
  cursor: pointer;
`;

export const Subtitle = styled.span`
  display: inline-block;

  color: #868f96;
  font-size: 14px;

  max-width: 100%;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
`;

export const Controls = styled.div`
  position: absolute;
  right: ${spacings.sm};
  top: ${spacings.sm};
  z-index: 2;

  display: flex;
  justify-content: end;
  align-items: stretch;
  gap: ${spacings.sm};

  opacity: 0;
  transform: translateY(-20px);
  transition: all 0.2s ease-out;
`;

export const ControlButton = styled.button`
  font-size: 14px;
  color: #868f96;

  > svg {
    fill: currentColor;
  }
`;

export const LinkList = styled.ul`
  margin: ${spacings.sm} 0 0;
  padding: 0;
`;

export const CardWrapper = styled.li`
  position: relative;

  display: flex;
  flex-direction: column;
  justify-content: space-between;

  overflow: hidden;

  background-color: #fcfdff;
  border: 1px solid #e7eef7;
  border-radius: var(--large-border-radius);

  opacity: 1;
  pointer-events: auto;

  transition:
    background-color 0.2s ease-out,
    border-color 0.2s ease-out;

  &.disabled {
    opacity: 0.5;
    pointer-events: none;
  }

  &.archived {
    opacity: 0;
  }

  &:not(.dragging):hover {
    background-color: #f3f7fd;
    border-color: #9eb0c5;

    ${TitleLink} {
      color: var(--link-color);
    }

    ${Controls} {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;

export const PaddedChartFooter = styled.div<{ $color: string }>`
  margin-top: -3px;

  background-color: ${({ $color }) => $color};
  opacity: 0.3;

  height: 5px;

  font-size: 1px;
  line-height: 1px;

  overflow: hidden;
`;
