import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

const generatePadding = (level = 1): string => {
  if (level > 10) return '';

  return `& > li {
    > label {
      padding-left: ${level * 10 + 20}px;

      &.has-children {
        padding-left: ${(level + 1) * 12}px;
      }
    }

    > ul {
      ${generatePadding(level + 1)}
    }
  }`;
};

export const List = styled.ul`
  margin: 0;
  padding: 0;

  ul {
    ${generatePadding()}
  }
`;

export const CheckMark = styled.div`
  position: absolute;
  left: 8px;
  top: 7px;

  width: 16px;
  font-size: 18px;
  font-weight: bold;

  fill: ${colors.gray500};
`;

export const LabelIcon = styled.div``;

export const LabelContainer = styled.div`
  display: inline-flex;
  justify-content: start;
  align-items: center;
  gap: ${spacings.sm};

  > svg {
    width: 16px;
    height: 16px;
  }
`;

export const LabelValueDisplay = styled.div`
  color: ${colors.gray300};
  font-size: 11px;
  font-style: italic;
  line-height: 11px;
  height: 11px;
`;

export const Label = styled.label`
  display: block;
  padding: 5px 14px 5px 30px;

  user-select: none;

  &:hover {
    cursor: pointer;
    background-color: ${colors.gray500};
    color: ${colors.white};

    ${CheckMark} {
      fill: ${colors.white};
    }
  }

  &.has-children {
    position: relative;

    padding-left: 12px;

    text-transform: uppercase;
    font-weight: bold;

    font-size: 12px;

    color: #7d8c9d;
    fill: currentColor;

    > ${LabelContainer} {
      position: relative;

      padding: 0 10px;
      background-color: ${colors.gray050};

      z-index: 1;
    }

    &:hover {
      cursor: default;
      background-color: transparent;
    }

    &:before {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      top: 13px;

      height: 1px;
      background-color: ${colors.gray200};
    }
  }
`;

export const Item = styled.li`
  position: relative;

  &.focused {
    > ${Label} {
      background-color: #cfd8e3;
      color: ${colors.gray700};

      > ${CheckMark} {
        fill: ${colors.gray700};
      }
    }
  }

  &.has-children {
    > ${Label} {
    }
  }

  &.empty {
    > ${Label} {
      color: ${colors.gray300};
      font-style: italic;

      &:hover {
        color: ${colors.white};
      }
    }

    &.focused {
      > ${Label} {
        background-color: transparent;

        &:hover {
          background-color: ${colors.gray500};
          color: ${colors.white};
        }

        > ${CheckMark} {
          fill: transparent;
        }
      }
    }
  }
`;
