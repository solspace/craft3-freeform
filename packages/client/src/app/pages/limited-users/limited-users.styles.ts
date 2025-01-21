import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const ContentContainer = styled.div`
  &.craft-4 {
    max-width: calc(100% - 250px) !important;
    width: calc(100% - 250px) !important;
  }
`;

export const GroupWrapper = styled.div`
  background-color: white;
  padding: ${spacings.xl};
  border-radius: 5px;
`;

export const TitleBlock = styled.div`
  display: flex;
  gap: 30px;

  align-self: center;
`;

export const Block = styled.div`
  display: grid;
  grid-template-columns: 41px auto;
  grid-template-areas: 'control label';

  gap: 5px 30px;

  padding: 0 0 14px;

  &.solo {
    display: flex;
  }

  &.triage {
    grid-template-areas:
      'control label'
      'control control-area';
  }
`;

export const Label = styled.label`
  grid-area: label;
`;

export const Heading = styled.h2`
  margin: 0;
  padding: 0;
`;

export const Control = styled.div`
  grid-area: control;
`;

export const ControlArea = styled.div`
  grid-area: control-area;
`;

export const ToggleList = styled.ul`
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 6px;
`;

export const ToggleListItem = styled.li`
  cursor: pointer;
  position: relative;

  padding: 3px 10px 3px 30px;
  background-color: ${colors.gray100};

  border-radius: 5px;

  user-select: none;
  transition: background-color 0.2s ease-in-out;

  i {
    position: absolute;
    left: 10px;
    top: 3px;
    font-size: 18px;
  }

  &:hover {
    background-color: ${colors.gray200};
  }

  &.selected {
    background-color: #1fa07a;
    color: white;

    &:hover {
      background-color: #1a8665;
    }
  }
`;

export const Actions = styled.div`
  display: flex;

  a {
    cursor: pointer;

    position: relative;
    padding: 0 10px;

    color: ${colors.blue600};

    &.disabled {
      color: ${colors.gray400};
      opacity: 0.5;
      cursor: not-allowed;
    }

    &:not(:last-child):after {
      position: absolute;
      right: -1px;
      top: 3px;

      content: '';

      display: block;
      width: 1px;
      height: 14px;

      background-color: ${colors.gray200};
      font-size: 0;
      line-height: 0;
      overflow: hidden;
    }
  }
`;

export const List = styled.ul`
  transition: opacity 0.2s ease-out;
`;

export const ListItem = styled.li`
  position: relative;

  &[data-disabled] {
    opacity: 0.5;
    pointer-events: none;
  }

  &[data-type='group'] {
    &[data-nesting='0']:not(:last-child) {
      padding-bottom: 30px;

      &:after {
        content: '';
        position: absolute;
        bottom: 20px;
        left: -24px;
        right: -24px;

        display: block;
        height: 1px;
        background-color: ${colors.gray100};
      }
    }
  }

  &[data-nesting='1'] h2 {
    margin-top: 10px !important;
    margin-left: 70px;
  }

  &[data-nesting='2'] {
    ${TitleBlock} {
      gap: 10px;

      &:before {
        content: '—';
      }
    }
  }

  &[data-nesting='3'] {
    ${TitleBlock} {
      gap: 10px;

      &:before {
        content: '———';
      }
    }
  }
`;
