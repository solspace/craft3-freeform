import React from 'react';
import { colors } from '@ff-client/styles/variables';
import styled from 'styled-components';

const chunkWidth = 22;

export const PreviewWrapper = styled.div`
  &.disabled {
    user-select: none;
    pointer-events: none;
    opacity: 0.3;

    transition: opacity 0.2s ease-out;
  }
`;

export const SelectAll = styled.a`
  cursor: pointer;
  display: block;

  color: ${colors.link} !important;
  margin-bottom: 10px;

  &:hover {
    cursor: pointer;
  }
`;

export const FileList = styled.div`
  padding: 10px;

  background: #f4f7fd;
  border: 1px solid #e1e5ea;
  border-radius: 3px;
`;

type LabelProps = {
  $light?: boolean;
};

export const Label = styled.label<LabelProps>`
  cursor: pointer;
  user-select: none;

  flex: 1;
  padding: 0 4px;

  text-align: left;
  font-weight: ${({ $light }) => ($light ? 'normal' : 'bold')};

  small {
    padding-left: 20px;
    font-size: 10px;
    opacity: 0.4;
  }
`;

export const Blocks = styled.div`
  display: flex;
  justify-content: start;
  align-items: center;
`;

type SpacerProps = {
  $width?: number;
  $dash?: boolean;
};

export const Spacer = styled.div<SpacerProps>`
  position: relative;
  flex-basis: ${({ $width = 1 }) => $width * chunkWidth}px;

  &:before {
    content: '';

    position: absolute;
    left: 2px;
    right: 2px;
    top: -1px;

    display: ${({ $dash }) => ($dash ? 'block' : 'none')};
    height: 2px;

    background: #b9c6d7;
  }
`;

export const BlockItem = styled.div`
  display: flex;
  justify-content: center;
  align-items: center;

  flex: 0 0 ${chunkWidth}px;
  height: 24px;
`;

export const Icon = styled.i`
  flex: 0 0 ${chunkWidth}px;
  font-size: 18px;

  text-align: center;

  img {
    width: 18px;
    height: 18px;
  }
`;

export const Directory: React.FC = () => {
  return <Icon className="fa-solid fa-folder" />;
};

export const File: React.FC = () => {
  return <Icon className="fa-light fa-file-code" />;
};

export const FormIcon: React.FC = () => {
  return <Icon className="fa-duotone fa-clipboard-list" />;
};

export const SubmissionIcon: React.FC = () => {
  return <Icon className="fa-duotone fa-inbox-in" />;
};

export const NotificationIcon: React.FC = () => {
  return <Icon className="fa-light fa-envelope" />;
};

export const PdfTemplateIcon: React.FC = () => {
  return <Icon className="fa-light fa-file-pdf" />;
};

export const FormattingIcon: React.FC = () => {
  return <Icon className="fa-light fa-file-code" />;
};

export const SuccessIcon: React.FC = () => {
  return <Icon className="fa-light fa-file-check" />;
};

export const IntegrationIcon: React.FC = () => {
  return <Icon className="fa-duotone fa-gear-complex-code" />;
};

export const SettingsIcon: React.FC = () => {
  return <Icon className="fa-duotone fa-gear" />;
};

type ListItemProps = {
  $selected?: boolean;
};

export const ListItem = styled.li<ListItemProps>`
  &.selectable:not(.selected) {
    ${Label}, ${Icon}, ${Spacer} {
      opacity: 0.4;
      transition: opacity 0.2s ease-out;
    }
  }
`;
