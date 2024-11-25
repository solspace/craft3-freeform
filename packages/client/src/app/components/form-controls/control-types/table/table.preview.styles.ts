import { labelText, scrollBar } from '@ff-client/styles/mixins';
import {
  borderRadius,
  colors,
  shadows,
  spacings,
} from '@ff-client/styles/variables';
import styled from 'styled-components';

export const NoContent = styled.div`
  position: absolute;
  top: calc(50% - 15px);
  left: 0;
  right: 0;

  opacity: 1;
  transition: opacity 0.2s ease-out;

  color: ${colors.gray200};
  font-size: 18px;
  font-weight: bold;
  font-style: italic;
  text-align: center;
`;

export const PreviewWrapper = styled.div`
  position: relative;

  &:after {
    content: attr(data-edit);
    pointer-events: none;

    position: absolute;
    left: 30px;
    top: calc(50% - 10px);

    width: 200px;

    opacity: 0;
    transition: opacity 0.2s ease-out;

    ${labelText};
    color: ${colors.gray300};
    font-size: 11px;
    text-align: center;
  }

  &:hover {
    &:after {
      opacity: 0.5;
    }

    ${NoContent} {
      opacity: 0;
    }
  }
`;

export const Header = styled.div`
  display: grid;
  grid-template-columns: 60% 40%;

  margin-bottom: ${spacings.md};

  ${labelText};
  font-size: 11px;
`;

export const PreviewTable = styled.div`
  height: 200px;
  max-height: 200px;
  overflow-x: hidden;
  overflow-y: auto;

  padding: 0 ${spacings.md};

  background: ${colors.white};
  box-shadow: ${shadows.box};
  border-radius: ${borderRadius.lg};

  ${scrollBar};
`;

export const PreviewRow = styled.div`
  position: relative;

  display: grid;
  grid-template-columns: auto 100px;
  gap: 10px;

  justify-items: stretch;
  align-items: center;

  border-bottom: 1px solid ${colors.gray100};

  &:after {
    content: attr(data-title);

    position: absolute;
    left: calc(100% - 105px);
    bottom: -4px;

    padding: 0 5px;
    background: ${colors.white};

    ${labelText};
    font-size: 8px;
    line-height: 8px;
  }

  > div {
    white-space: nowrap;
    overflow: hidden;

    padding: 7px ${spacings.xs} 7px 0;

    &:last-child {
      padding-right: 0;
    }
  }
`;

export const PreviewData = styled.div`
  &:empty {
    &:after {
      content: attr(data-empty);
      color: ${colors.gray200};
      font-size: 12px;
      font-style: italic;
    }
  }
`;
