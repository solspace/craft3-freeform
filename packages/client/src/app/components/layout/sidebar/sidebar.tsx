import { borderRadius, colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

type WrapperProps = {
  $lean?: boolean;
  $noPadding?: boolean;
};

export const Sidebar = styled.div<WrapperProps>`
  position: relative;

  flex-basis: 300px;
  flex-shrink: 0;
  width: 300px;
  padding: ${({ $lean, $noPadding }): string =>
    $lean ? spacings.sm : $noPadding ? '0' : spacings.lg};
  box-sizing: border-box;

  border-bottom-left-radius: ${borderRadius.lg};
  box-shadow: inset -1px 0 0 0 rgb(154 165 177 / 25%);
  background: ${colors.gray050};

  overflow-y: auto;

  --background-color: ${colors.gray050};
  --margins: -18px;
`;
