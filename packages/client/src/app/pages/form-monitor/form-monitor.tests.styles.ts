import { Link } from 'react-router-dom';
import { scrollBar } from '@ff-client/styles/mixins';
import { borderRadius, colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const CodeBlock = styled.div`
  position: relative;
  padding: ${spacings.sm} ${spacings.md};
  font-family: monospace;
  font-size: 12px;
  line-height: 1.4;
  background: ${colors.gray050};
  border: 1px solid ${colors.gray200};
  border-radius: ${borderRadius.md};
  max-height: 60px;
  overflow-y: auto;
  ${scrollBar};

  &:hover {
    max-height: none;
  }
`;

export const TooltipContainer = styled.div`
  padding: ${spacings.md};
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  border-radius: ${borderRadius.md};
  font-size: 12px;
  line-height: 1.5;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);

  div {
    &:not(:last-child) {
      margin-bottom: ${spacings.xs};
    }
  }
`;

export const StatsContainer = styled.div`
  padding: ${spacings.xl};
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  border-radius: ${borderRadius.lg} ${borderRadius.lg} 0 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
`;

export const StatsHeader = styled.div`
  margin-bottom: ${spacings.lg};

  h2 {
    margin-bottom: ${spacings.md};
    font-size: 20px;
    font-weight: 600;
    color: ${colors.gray800};
  }
`;

export const StatsOverview = styled.div`
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: ${spacings.lg};
  margin-bottom: ${spacings.xl};
`;

export const StatBox = styled.div<{ color: string }>`
  padding: ${spacings.lg};
  text-align: center;
  border-radius: ${borderRadius.md};
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
  transition: all 0.2s ease;

  &:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
  }

  strong {
    display: block;
    font-size: 28px;
    font-weight: 600;
    color: ${(props) => props.color};
    margin-bottom: ${spacings.xs};
  }

  span {
    font-size: 13px;
    color: ${colors.gray600};
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
`;

export const ChartContainer = styled.div`
  margin-top: ${spacings.lg};
  padding-top: ${spacings.lg};
  border-top: 1px solid ${colors.gray100};
`;

export const TestTableStyled = styled.table`
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  border-radius: 0 0 ${borderRadius.lg} ${borderRadius.lg};
  border-top: none;
  overflow: hidden;
  margin-top: -1px;

  thead {
    background: ${colors.gray050};

    th {
      padding: ${spacings.md} ${spacings.lg};
      font-weight: 600;
      color: ${colors.gray700};
      text-align: left;
      white-space: nowrap;
      border-bottom: 1px solid ${colors.gray200};
    }
  }

  tbody {
    td {
      padding: ${spacings.md} ${spacings.lg};
      border-bottom: 1px solid ${colors.gray100};
      vertical-align: middle;

      &.no-break {
        white-space: nowrap;
      }
    }

    tr:last-child td {
      border-bottom: none;
    }

    tr:hover {
      background: ${colors.gray050};
    }
  }

  .status-col {
    display: flex;
    gap: ${spacings.sm};
    align-items: center;
    padding: ${spacings.lg};
  }
`;

export const StatusBadgeStyled = styled.div`
  padding: ${spacings.xs} ${spacings.sm};
  border-radius: ${borderRadius.sm};
  font-size: 12px;
  font-weight: 500;
  color: ${colors.white};
  background-color: ${colors.teal500};
  text-transform: uppercase;
  letter-spacing: 0.5px;

  &.status-pending {
    background-color: ${colors.yellow400};
  }

  &.status-failed {
    background-color: ${colors.red500};
  }
`;

export const FormLink = styled(Link)`
  color: ${colors.blue500};
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
`;

export const ChartDescription = styled.div`
  margin-bottom: ${spacings.md};
  color: ${colors.gray600};
  font-size: 13px;
  line-height: 1.5;

  strong {
    color: ${colors.gray700};
    font-weight: 500;
  }
`;

export const ChartLegend = styled.div`
  display: flex;
  gap: ${spacings.lg};
  margin-bottom: ${spacings.md};
  font-size: 12px;
`;

export const LegendItem = styled.div<{ color: string }>`
  display: flex;
  align-items: center;
  gap: ${spacings.xs};
  color: ${colors.gray600};

  &::before {
    content: '';
    display: block;
    width: 8px;
    height: 8px;
    border-radius: 2px;
    background-color: ${(props) => props.color};
  }
`;

export const PaginationContainer = styled.div`
  display: flex;
  align-items: center;
  gap: ${spacings.md};
  margin-top: ${spacings.xl};
  padding-top: ${spacings.lg};
  border-top: 1px solid ${colors.gray200};
`;

export const PaginationNav = styled.nav`
  display: flex;
  gap: ${spacings.xs};
`;

export const PageButton = styled.button<{ disabled?: boolean }>`
  width: 32px;
  height: 32px;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: ${borderRadius.sm};
  background: ${colors.white};
  cursor: pointer;
  transition: all 0.2s ease;

  &:hover:not(:disabled) {
    border-color: ${colors.blue500};
    &::after {
      border-color: ${colors.blue500};
    }
  }

  &:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  &::after {
    content: '';
    display: block;
    width: 7px;
    height: 7px;
    border: solid ${colors.gray700};
    border-width: 0 2px 2px 0;
    opacity: 0.8;
    position: relative;
  }

  &.prev-page::after {
    transform: rotate(135deg);
    right: -1px;
  }

  &.next-page::after {
    transform: rotate(-45deg);
    left: -1px;
  }

  &:disabled::after {
    border-color: ${colors.gray300};
  }
`;

export const PageInfo = styled.div`
  color: ${colors.gray600};
  font-size: 13px;
`;
