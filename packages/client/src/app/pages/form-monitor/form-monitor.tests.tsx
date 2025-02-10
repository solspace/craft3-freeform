import React, { useEffect, useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { useQueryFormsWithStats } from '@ff-client/queries/forms';
import { colors } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import {
  format,
  formatDistanceToNow,
  isToday,
  isYesterday,
  parseISO,
} from 'date-fns';
import {
  Bar,
  BarChart,
  CartesianGrid,
  Cell,
  ResponsiveContainer,
  Tooltip,
  XAxis,
  YAxis,
} from 'recharts';

import { FMEmptyTests } from './form-monitor.empty';
import type { FormTest, TestStats } from './form-monitor.queries';
import { useFMFormTestsQuery } from './form-monitor.queries';
import { ScreenshotModal } from './form-monitor.screenshot.modal';
import { FormMonitorWrapper } from './form-monitor.styles';
import { TestsLoader } from './form-monitor.tests.loader';
import {
  ChartContainer,
  ChartDescription,
  ChartLegend,
  CodeBlock,
  FormLink,
  LegendItem,
  PageButton,
  PageInfo,
  PaginationContainer,
  PaginationNav,
  StatBox,
  StatsContainer,
  StatsHeader,
  StatsOverview,
  StatusBadgeStyled,
  TestTableStyled,
  TooltipContainer,
} from './form-monitor.tests.styles';

const formatTestDate = (dateString: string): string => {
  const date = parseISO(dateString);
  const time = format(date, 'h:mm a');

  if (isToday(date)) {
    return `Today at ${time}`;
  }

  if (isYesterday(date)) {
    return `Yesterday at ${time}`;
  }

  // If within last 7 days, show day name
  if (formatDistanceToNow(date).includes('days')) {
    return `${format(date, 'EEEE')} at ${time}`;
  }

  // Otherwise show full date
  return `${format(date, 'MMM d')} at ${time}`;
};

interface TestStatsProps {
  tests: FormTest[];
  stats: TestStats;
}

interface TooltipProps {
  active?: boolean;
  payload?: Array<{
    payload: {
      id: number;
      date: string;
      formId: number;
      status: string;
      color: string;
    };
  }>;
}

const TestStats: React.FC<TestStatsProps> = ({ tests, stats }) => {
  // Transform tests data for the chart
  const chartData = tests.map((test) => ({
    id: test.id,
    status: test.status,
    date: formatTestDate(test.dateCompleted || test.dateAttempted),
    formId: test.formId,
    color:
      test.status === 'success'
        ? colors.teal500
        : test.status === 'failed'
          ? colors.red500
          : colors.yellow400,
    value: 1,
  }));

  const CustomTooltip = ({
    active,
    payload,
  }: TooltipProps): JSX.Element | null => {
    if (!active || !payload?.length) return null;

    const data = payload[0].payload;
    return (
      <TooltipContainer>
        <div>Test #{data.id}</div>
        <div>{data.date}</div>
        <div>Form #{data.formId}</div>
        <div style={{ color: data.color, textTransform: 'capitalize' }}>
          {data.status}
        </div>
      </TooltipContainer>
    );
  };

  return (
    <StatsContainer>
      <StatsHeader>
        <h2>{translate('Test Results')}</h2>
        <StatsOverview>
          <StatBox color={colors.teal500}>
            <strong>{stats.percentage.success}%</strong>
            <span>{translate('Success Rate')}</span>
          </StatBox>
          <StatBox color={colors.red500}>
            <strong>{stats.percentage.failed}%</strong>
            <span>{translate('Failure Rate')}</span>
          </StatBox>
          <StatBox color={colors.yellow400}>
            <strong>{stats.percentage.pending}%</strong>
            <span>{translate('Pending Rate')}</span>
          </StatBox>
        </StatsOverview>
      </StatsHeader>

      <ChartContainer>
        <ChartDescription>
          {translate(
            'Each bar represents a single test, with color indicating the status. ' +
              'Hover over any bar to see detailed information. ' +
              'The most recent tests are shown on the right.'
          )}
        </ChartDescription>
        <ChartLegend>
          <LegendItem color={colors.teal500}>{translate('Success')}</LegendItem>
          <LegendItem color={colors.red500}>{translate('Failed')}</LegendItem>
          <LegendItem color={colors.yellow400}>
            {translate('Pending')}
          </LegendItem>
        </ChartLegend>
        <ResponsiveContainer width="100%" height={100}>
          <BarChart
            data={chartData}
            margin={{ top: 20, right: 30, left: 20, bottom: 5 }}
            barGap={0}
            barSize={8}
          >
            <CartesianGrid strokeDasharray="3 3" vertical={false} />
            <XAxis
              dataKey="id"
              label={{ value: 'Test #', position: 'bottom' }}
              tick={false}
            />
            <YAxis hide />
            <Tooltip content={<CustomTooltip />} />
            <Bar dataKey="value" fill={colors.teal500}>
              {chartData.map((entry, index) => (
                <Cell key={`cell-${index}`} fill={entry.color} />
              ))}
            </Bar>
          </BarChart>
        </ResponsiveContainer>
      </ChartContainer>
    </StatsContainer>
  );
};

export const FMTests: React.FC = () => {
  const { formId } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const ITEMS_PER_PAGE = 100;

  const currentPage = Number(searchParams.get('page')) || 1;
  const offset = currentPage > 0 ? (currentPage - 1) * ITEMS_PER_PAGE : 0;

  const { data: forms } = useQueryFormsWithStats();
  const {
    data: formTests,
    isFetching,
    error: formTestsError,
  } = useFMFormTestsQuery(Number(formId), {
    limit: ITEMS_PER_PAGE,
    offset,
  });

  useSidebarSelect('/freeform/form-monitor');

  const handlePageChange = (page: number): void => {
    setSearchParams({ page: String(page) });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Ensure we reset to page 1 if total pages changes and current page is out of bounds
  useEffect(() => {
    if (
      formTests?.pagination.totalPages &&
      currentPage > formTests.pagination.totalPages
    ) {
      setSearchParams({ page: String(formTests.pagination.totalPages) });
    }
  }, [formTests?.pagination.totalPages, currentPage, setSearchParams]);

  const [selectedScreenshot, setSelectedScreenshot] = useState<{
    url: string;
    testId: number;
  } | null>(null);

  return (
    <FormMonitorWrapper>
      {selectedScreenshot && (
        <ScreenshotModal
          imageUrl={selectedScreenshot.url}
          testId={selectedScreenshot.testId}
          onClose={() => setSelectedScreenshot(null)}
        />
      )}
      {formTestsError && <FMEmptyTests error />}
      {(formTests === undefined || isFetching) && <TestsLoader />}
      {!isFetching && formTests && formTests?.tests.length === 0 && (
        <FMEmptyTests />
      )}
      {!isFetching && formTests !== undefined && formTests.tests.length > 0 && (
        <>
          <TestStats tests={formTests.tests} stats={formTests.stats} />
          <TestTableStyled>
            <thead>
              <tr>
                <th>{translate('Test ID')}</th>
                <th>{translate('Date')}</th>
                <th>{translate('Form')}</th>
                <th>{translate('Status')}</th>
                <th>{translate('Response')}</th>
                <th>{translate('Screenshot')}</th>
              </tr>
            </thead>
            <tbody>
              {formTests?.tests?.map((test) => {
                const form = forms?.find((form) => form.id === test.formId);
                if (!form) {
                  return null;
                }

                const dateString = test.dateCompleted || test.dateAttempted;
                const formattedDate = formatTestDate(dateString);

                return (
                  <tr key={test.id}>
                    <td className="no-break">#{test.id}</td>
                    <td
                      className="no-break"
                      title={format(parseISO(dateString), 'PPP p')}
                    >
                      {formattedDate}
                    </td>
                    <td>
                      <FormLink to={`/forms/${form.id}`}>{form.name}</FormLink>
                    </td>
                    <td className="no-break">
                      <div className="status-col">
                        <StatusBadgeStyled className={`status-${test.status}`}>
                          {test.responseCode}
                        </StatusBadgeStyled>
                        <div>{test.status}</div>
                      </div>
                    </td>

                    <td className="code" title={test.response}>
                      {!!test.response && (
                        <CodeBlock>{test.response}</CodeBlock>
                      )}
                    </td>
                    <td>
                      {test.screenshot && (
                        <img
                          src={test.screenshot}
                          alt={`Test #${test.id} screenshot`}
                          width={100}
                          height="auto"
                          onClick={() =>
                            setSelectedScreenshot({
                              url: test.screenshot!,
                              testId: test.id,
                            })
                          }
                          style={{ cursor: 'pointer' }}
                        />
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </TestTableStyled>
          <PaginationContainer>
            <PaginationNav aria-label="test results pagination">
              <PageButton
                className="prev-page"
                onClick={() => handlePageChange(currentPage - 1)}
                disabled={currentPage === 1}
                title={translate('Previous Page')}
              />
              <PageButton
                className="next-page"
                onClick={() => handlePageChange(currentPage + 1)}
                disabled={currentPage === formTests.pagination.totalPages}
                title={translate('Next Page')}
              />
            </PaginationNav>
            <PageInfo>
              {translate('Showing')} {formTests.pagination.offset + 1}-
              {Math.min(
                formTests.pagination.offset + formTests.pagination.limit,
                formTests.pagination.total
              )}{' '}
              {translate('of')} {formTests.pagination.total}{' '}
              {translate('tests')}
            </PageInfo>
          </PaginationContainer>
        </>
      )}
    </FormMonitorWrapper>
  );
};
