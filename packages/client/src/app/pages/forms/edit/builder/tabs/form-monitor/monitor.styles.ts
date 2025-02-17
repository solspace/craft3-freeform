import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const FormMonitorWrapper = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.lg};
  padding: ${spacings.xl};
  background: ${colors.white};
  height: 100%;
  flex: 1;
`;

export const MonitorWrapper = styled.div`
  display: flex;
  flex-grow: 1;
  height: 100%;
`;
