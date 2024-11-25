import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const OptionContainer = styled.div`
  display: flex;
  align-items: center;
  gap: 4px;

  padding: 0 8px;

  &:not(:last-child) {
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  }
`;

export const TextContainer = styled.div`
  display: flex;
  align-items: center;

  input:first-child {
    border-right: 1px solid rgba(0, 0, 0, 0.1);
  }
`;

const Button = styled.button`
  width: 20px;
  height: 20px;

  padding: 2px;
  margin: 0;
  border: 0;

  &:before {
    content: 'plus';

    color: ${colors.gray500};

    font-family: Craft;
    font-size: 15px;
    font-weight: 100;
    line-height: 15px;
  }
`;

export const AddButton = styled(Button)`
  right: 20px;

  &:before {
    content: 'plus';
  }
`;

export const RemoveButton = styled(Button)`
  &:before {
    content: 'minus';
  }
`;

export const CheckboxContainer = styled.div`
  display: flex;
  justify-content: start;
  align-items: center;
  gap: 5px;

  padding: 0 8px;

  label {
    display: block;
  }
`;
