import { borderRadius, colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const Wrapper = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.lg};
  padding: ${spacings.md};

  h3 {
    margin: 0;
    font-size: 1.1em;
  }
`;

export const ChartContainer = styled.div`
  border-radius: ${borderRadius.md};
  padding: ${spacings.md};
  margin-bottom: ${spacings.md};
`;

export const ChartDescription = styled.p`
  color: ${colors.gray600};
  font-size: 0.9em;
  text-align: center;
  margin: ${spacings.md} 0 0;
`;

export const NoResults = styled.div`
  color: ${colors.gray600};
  font-size: 0.9em;
  text-align: center;
  padding: ${spacings.xl} 0;
`;

export const TotalCount = styled.div`
  font-size: 13px;
  font-weight: 600;
  color: ${colors.gray800};
  margin-bottom: ${spacings.sm};
  text-align: center;
`;

export const StatContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.md};
`;

export const StatRow = styled.div`
  display: flex;
  flex-direction: column;
  gap: 6px;
`;

export const StatHeader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 12px;
`;

export const StatLabel = styled.div<{
  $type: 'success' | 'failed' | 'pending';
}>`
  color: ${({ $type }) =>
    $type === 'success'
      ? colors.teal700
      : $type === 'failed'
        ? colors.red700
        : colors.yellow700};
  font-weight: 500;
`;

export const StatValue = styled.div`
  color: ${colors.gray700};
  font-weight: 500;
`;

export const ProgressBar = styled.div`
  height: 8px;
  background: ${colors.gray100};
  border-radius: 4px;
  overflow: hidden;
`;

export const Progress = styled.div<{
  $type: 'success' | 'failed' | 'pending';
  $percentage: number;
}>`
  height: 100%;
  width: ${({ $percentage }) => $percentage}%;
  background: ${({ $type }) =>
    $type === 'success'
      ? colors.teal500
      : $type === 'failed'
        ? colors.red500
        : colors.yellow400};
  transition: width 0.3s ease;
`;
