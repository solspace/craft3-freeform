import type { PropsWithChildren } from 'react';
import React from 'react';
import { Link } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { ContentContainer } from '@components/layout/blocks/content-container';
import { HeaderContainer } from '@components/layout/blocks/header-container';
import { colors } from '@ff-client/styles/variables';
import { spacings } from '@ff-client/styles/variables';
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
          <LayoutContainer id="content">{children}</LayoutContainer>
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

  &.status-pending {
    background-color: ${colors.yellow400};
  }

  &.status-failed {
    background-color: ${colors.red500};
  }
`;

export const Cards = styled.ul`
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: ${spacings.lg};
`;

export const Card = styled(Link)`
  border: 1px solid ${colors.gray200};
  border-radius: 5px;

  &:hover {
    background-color: ${colors.gray050};
    text-decoration: none;
  }
`;

export const CardContent = styled.div`
  display: flex;
  justify-content: space-between;
  border-radius: 5px;
`;

export const FormCardContent = styled.div`
  padding: ${spacings.xl} ${spacings.xl};
`;

export const Title = styled.h2`
  cursor: pointer;

  color: #3d464e;

  font-size: 20px;
  font-weight: 700;
  text-align: left;

  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;

  transition: all 0.2s ease-out;

  &:hover {
    border: none;
  }
`;

export const StatsChartContainer = styled.div`
  display: flex;
  align-items: center;
  gap: ${spacings.md};
  padding: ${spacings.md};
`;

export const Legend = styled.div`
  display: flex;
  gap: ${spacings.lg};
  margin-top: ${spacings.md};
`;

export const LegendItem = styled.div<{ color: string }>`
  display: flex;
  align-items: center;
  gap: ${spacings.xs};

  &:before {
    content: '';
    display: block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background-color: ${(props) => props.color};
  }
`;

export const LayoutContainer = styled.div`
  gap: ${spacings.xl};
  align-items: flex-start;

  ${Cards} {
    flex: 2;
  }

  ${StatsChartContainer} {
    position: sticky;
    top: ${spacings.xl};
  }
`;

export const LoaderCard = styled.div`
  border: 1px solid ${colors.gray200};

  ${Card}
`;
