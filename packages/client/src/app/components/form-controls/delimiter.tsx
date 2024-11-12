import React from 'react';
import type { Delimiter } from '@ff-client/types/properties';

type Props = {
  delimiter?: Delimiter;
};

import { labelText } from '@ff-client/styles/mixins';
import { shadows } from '@ff-client/styles/variables';
import styled from 'styled-components';

const DelimiterWrapper = styled.div`
  position: relative;
  min-height: 10px;

  &:before {
    content: '';
    position: absolute;
    top: 9px;
    left: 0;
    right: 0;
    z-index: 1;

    height: 1px;
    margin: 0 var(--margins);

    box-shadow: ${shadows.bottom};
  }

  div {
    position: absolute;
    left: -5px;
    top: 0;
    z-index: 2;

    background: var(--background-color);
    padding: 0 5px;

    ${labelText};
    font-size: 11px;

    &:empty {
      display: none;
    }
  }
`;

export const DelimiterElement: React.FC<Props> = ({ delimiter }) => {
  if (!delimiter) {
    return null;
  }

  return (
    <DelimiterWrapper>
      <div>{delimiter.name}</div>
    </DelimiterWrapper>
  );
};
