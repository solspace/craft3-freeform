import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const MonitorStatus = styled.span<{ $type: 'active' | 'inactive' }>`
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  border-radius: 3px;
  font-size: 9px;
  line-height: 1.2;
  font-weight: 500;

  ${({ $type }) =>
    $type === 'active'
      ? `
          color: ${colors.teal900};
          background-color: ${colors.teal100};
        `
      : `
          color: ${colors.gray700};
          background-color: ${colors.gray100};
        `}
`;
