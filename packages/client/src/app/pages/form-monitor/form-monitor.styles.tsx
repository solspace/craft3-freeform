import type { PropsWithChildren } from 'react';
import React from 'react';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { ContentContainer } from '@components/layout/blocks/content-container';
import { HeaderContainer } from '@components/layout/blocks/header-container';
import { colors } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import styled from 'styled-components';

export const FormMonitorWrapper: React.FC<PropsWithChildren> = ({
  children,
}) => {
  return (
    <div>
      <Breadcrumb
        id="form-monitor"
        label={translate('Form Monitor')}
        url="/form-monitor"
      />

      <HeaderContainer>{translate('Form Monitor')}</HeaderContainer>

      <div id="main-content">
        <ContentContainer>
          <div id="content">{children}</div>
        </ContentContainer>
      </div>
    </div>
  );
};

export const TestBlock = styled.div`
  display: flex;
  gap: 5px;
`;

export const TestTable = styled.table`
  thead th {
    white-space: nowrap;
  }

  tbody td {
    vertical-align: top;

    &.no-break {
      white-space: nowrap;
    }
  }

  .status-col {
    display: flex;
    gap: 5px;
    align-items: center;
  }
`;

export const StatusBadge = styled.div`
  padding: 1px 5px;

  border-radius: 5px;

  font-size: 10px;
  color: ${colors.white};
  background-color: ${colors.teal500};

  &.status-failed {
    background-color: ${colors.red500};
  }
`;
