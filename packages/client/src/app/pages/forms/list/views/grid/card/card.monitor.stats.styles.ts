import { spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const StatsChartContainer = styled.div`
  display: flex;
  align-items: center;
  gap: ${spacings.md};
`;

export const LegendItem = styled.div<{ color: string }>`
  display: flex;
  align-items: center;
  gap: ${spacings.xs};

  &:before {
    content: '';
    display: block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: ${(props) => props.color};
  }
`;
