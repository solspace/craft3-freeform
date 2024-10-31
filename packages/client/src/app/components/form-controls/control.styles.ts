import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

import { Label } from './label.styles';

export const Instructions = styled.span`
  display: block;

  color: ${colors.gray300};
  padding-top: 0;
  line-height: 16px;
  font-size: 12px;
  font-style: italic;

  &:not(:last-child) {
    padding-bottom: 6px;
  }

  code {
    padding: 1px 4px;
    border-radius: 3px;
    background-color: #dfe5ec;

    font-family: monospace;
    font-style: normal;
    color: ${colors.gray600};
  }
`;

export const FormField = styled.div`
  margin: 0;
  padding: 0;
  width: 100%;
  display: block;

  &.disabled {
    user-select: none;
    pointer-events: none;
    opacity: 0.5;
  }
`;

type ControlWrapperProps = {
  $width?: number;
};

export const ControlWrapper = styled.div<ControlWrapperProps>`
  display: flex;
  position: relative;
  flex-direction: column;
  align-items: flex-start;
  justify-content: flex-start;

  width: ${({ $width }) => ($width ? `${$width}%` : '100%')};

  &.disabled {
    opacity: 0.5;
    user-select: none;
    pointer-events: none;
  }

  &.errors {
    ${Label} {
      color: ${colors.error};
    }

    ${FormField} {
      input,
      textarea,
      select {
        border: 1px solid ${colors.error};
      }

      select {
        background-color: var(--ui-control-bg-color);

        &:hover {
          background-color: var(--ui-control-hover-bg-color);
        }
      }
    }
  }

  &.spacing-small {
    padding-top: 6px;
  }

  .btn {
    background-color: var(--ui-control-bg-color);

    &:hover {
      background-color: var(--ui-control-hover-bg-color);
    }
  }
`;
