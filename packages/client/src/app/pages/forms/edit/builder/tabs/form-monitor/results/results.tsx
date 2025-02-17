import React from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import { useFMFormTestsQuery } from '@ff-client/queries/form-monitor';
import { colors } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import { format } from 'date-fns';
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

import { FormMonitorDetailsLoader } from '../form-monitor.loader';
import { ScreenshotModal } from '../form-monitor.screenshot.modal';

import {
  ChartContainer,
  ChartDescription,
  ChartLegend,
  CodeBlock,
  LegendItem,
  NoResults,
  PageButton,
  PageInfo,
  PaginationContainer,
  PaginationNav,
  ResultsWrapper,
  StatsContainer,
  StatusBadgeStyled,
  TestTableStyled,
  TooltipContainer,
  TooltipContent,
  TooltipDate,
  TooltipStatus,
} from './results.styles';

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

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return format(date, "MMM d, yyyy 'at' h:mm a");
};

const CustomTooltip = ({
  active,
  payload,
}: TooltipProps): JSX.Element | null => {
  if (!active || !payload?.length) return null;

  const data = payload[0].payload;
  return (
    <TooltipContainer>
      <TooltipContent>
        <TooltipStatus $status={data.status}>
          Test #{data.id}
          <br />
          {data.date}
          <br />
          <div style={{ color: data.color, textTransform: 'capitalize' }}>
            {data.status}
          </div>
        </TooltipStatus>
        <TooltipDate>{data.date}</TooltipDate>
      </TooltipContent>
    </TooltipContainer>
  );
};

export const FMResults: React.FC = () => {
  const { formId } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();
  const ITEMS_PER_PAGE = 100;
  const currentPage = Number(searchParams.get('page')) || 1;
  const offset = currentPage > 0 ? (currentPage - 1) * ITEMS_PER_PAGE : 0;
  const [selectedScreenshot, setSelectedScreenshot] = React.useState<{
    url: string;
    testId: number;
  } | null>(null);

  const {
    data: formTests,
    isLoading,
    isFetching,
  } = useFMFormTestsQuery(Number(formId), {
    limit: ITEMS_PER_PAGE,
    offset,
  });

  if (isLoading || isFetching) {
    return <FormMonitorDetailsLoader />;
  }

  if (!formTests) return null;

  if (formTests.tests.length === 0) {
    return (
      <ResultsWrapper>
        <NoResults>
          <p>{translate('No test results have been recorded yet.')}</p>
        </NoResults>
      </ResultsWrapper>
    );
  }

  const chartData = formTests.tests.map((test) => ({
    id: test.id,
    value: 1,
    status: test.status,
    date: formatDate(test.dateAttempted),
    color:
      test.status === 'success'
        ? colors.teal500
        : test.status === 'failed'
          ? colors.red500
          : colors.yellow400,
  }));

  const handlePageChange = (page: number): void => {
    setSearchParams({ page: String(page) });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  return (
    <ResultsWrapper>
      <StatsContainer>
        <ChartContainer>
          <ChartDescription>
            {translate(
              'Each bar represents a single test, with color indicating the status. ' +
                'Hover over any bar to see detailed information. ' +
                'The most recent tests are shown on the right.'
            )}
          </ChartDescription>
          <ChartLegend>
            <LegendItem color={colors.teal500}>
              {translate('Success')}
            </LegendItem>
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

      <TestTableStyled>
        <thead>
          <tr>
            <th>{translate('Test ID')}</th>
            <th>{translate('Date')}</th>
            <th>{translate('Status')}</th>
            <th>{translate('Response')}</th>
            <th>{translate('Screenshot')}</th>
          </tr>
        </thead>
        <tbody>
          {formTests.tests.map((test) => {
            const dateString = test.dateCompleted || test.dateAttempted;
            return (
              <tr key={test.id}>
                <td className="no-break">#{test.id}</td>
                <td className="no-break" title={formatDate(dateString)}>
                  {formatDate(dateString)}
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
                  {!!test.response && <CodeBlock>{test.response}</CodeBlock>}
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

      {formTests.pagination.total > ITEMS_PER_PAGE && (
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
            {translate('of')} {formTests.pagination.total} {translate('tests')}
          </PageInfo>
        </PaginationContainer>
      )}

      {selectedScreenshot && (
        <ScreenshotModal
          imageUrl={selectedScreenshot.url}
          testId={selectedScreenshot.testId}
          onClose={() => setSelectedScreenshot(null)}
        />
      )}
    </ResultsWrapper>
  );
};
