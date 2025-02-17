import { scrollBar } from '@ff-client/styles/mixins';
import { borderRadius, colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const ResultsWrapper = styled.div`
  flex: 1;

  background: ${colors.white};
  padding: ${spacings.xl};
  overflow-y: auto;
  width: calc(100% - 300px);

  ${scrollBar};

  div[class^='ControlWrapper-'] {
    div[class^='CheckboxWrapper-'] {
      align-items: start;

      div[class^='CheckboxItem-'] {
        padding-top: 4px;
      }
    }
  }

  h2 {
    margin: 0;
    font-size: 1.5em;
  }
`;

export const NoResults = styled.div`
  color: ${colors.gray700};

  p {
    color: ${colors.gray600};
    font-size: 0.9em;
  }
`;

export const StatsContainer = styled.div`
  padding: ${spacings.md};
  border-radius: ${borderRadius.lg} ${borderRadius.lg} 0 0;
`;

export const ChartContainer = styled.div`
  background: ${colors.white};
  border-radius: 4px;
`;

export const ChartDescription = styled.p`
  color: ${colors.gray600};
  font-size: 0.9em;
  margin-bottom: ${spacings.md};
`;

export const ChartLegend = styled.div`
  display: flex;
  gap: ${spacings.md};
  margin-bottom: ${spacings.md};
`;

export const LegendItem = styled.div<{ color: string }>`
  display: flex;
  align-items: center;
  font-size: 0.9em;
  color: ${colors.gray700};

  &:before {
    content: '';
    display: inline-block;
    width: 12px;
    height: 12px;
    margin-right: ${spacings.xs};
    background: ${({ color }) => color};
    border-radius: 2px;
  }
`;

export const TooltipContainer = styled.div`
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  border-radius: 4px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
`;

export const TooltipContent = styled.div`
  padding: ${spacings.sm} ${spacings.md};
`;

export const TooltipStatus = styled.div<{ $status: string }>`
  font-weight: 500;
  color: ${({ $status }) =>
    $status === 'success'
      ? colors.teal700
      : $status === 'failed'
        ? colors.red700
        : colors.yellow700};
  margin-bottom: ${spacings.xs};
`;

export const TooltipDate = styled.div`
  font-size: 0.9em;
  color: ${colors.gray600};
`;

export const TestList = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.md};
`;

export const TestItem = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: ${spacings.md};
  background: ${colors.white};
  border-radius: 4px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
`;

export const TestInfo = styled.div`
  display: flex;
  align-items: center;
  gap: ${spacings.md};
`;

export const TestStatus = styled.div`
  text-transform: capitalize;
`;

export const TestDate = styled.div`
  color: ${colors.gray600};
  font-size: 0.9em;
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

export const TestTableStyled = styled.table`
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  background: ${colors.white};
  border: 1px solid ${colors.gray200};
  border-radius: ${borderRadius.lg};
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
