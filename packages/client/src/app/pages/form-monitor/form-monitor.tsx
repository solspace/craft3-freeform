import React from 'react';
import { EmptyBlock } from '@components/empty-block/empty-block';
import config, { Edition } from '@config/freeform/freeform.config';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { useQueryFormsWithStats } from '@ff-client/queries/forms';
import { colors } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import { Area, Legend } from 'recharts';
import { ResponsiveContainer } from 'recharts';
import { AreaChart } from 'recharts';
import { Cell, Pie, PieChart } from 'recharts';

import { PaddedChartFooter } from '../forms/list/views/grid/card/card.styles';

import { FormMonitorLoader } from './form-monitor.loader';
import { useFMForms, useFMFormStats } from './form-monitor.queries';
import {
  Card,
  CardContent,
  Cards,
  FormCardContent,
  FormMonitorWrapper,
  LegendItem,
  StatsChartContainer,
  Title,
} from './form-monitor.styles';

const randomSubmissions = (min: number, max: number): number =>
  Math.floor(Math.random() * (max - min + 1)) + min;

const randomData = Array.from({ length: 31 }, () => ({
  uv: randomSubmissions(0, Math.random() > 0.9 ? 50 : 20), // 15% chance for peak day
}));

const StatsChart: React.FC<{
  stats: {
    success: number;
    failed: number;
    pending: number;
    total: number;
    percentage: {
      success: number;
      failed: number;
      pending: number;
    };
  };
}> = ({ stats }) => {
  const total = stats?.total || 0;

  // If not monitored, show full circle in gray
  const data =
    total === 0
      ? [
          {
            name: 'Not Monitored',
            value: 100,
            color: colors.gray300,
          },
        ]
      : [
          {
            name: 'Success',
            value: stats?.percentage?.success || 0,
            color: colors.teal500,
          },
          {
            name: 'Failed',
            value: stats?.percentage?.failed || 0,
            color: colors.red500,
          },
          {
            name: 'Pending',
            value: stats?.percentage?.pending || 0,
            color: colors.yellow400,
          },
        ];

  return (
    <StatsChartContainer>
      <div style={{ width: 80, height: 80, position: 'relative' }}>
        <PieChart width={80} height={80}>
          <Pie
            data={data}
            cx={40}
            cy={40}
            innerRadius={25}
            outerRadius={35}
            startAngle={90}
            endAngle={-270}
            dataKey="value"
          >
            {data.map((entry, index) => (
              <Cell key={`cell-${index}`} fill={entry.color} />
            ))}
          </Pie>
        </PieChart>
        <div
          style={{
            position: 'absolute',
            top: 15,
            left: 10,
            right: 0,
            bottom: 0,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <strong
            style={{
              fontSize: 10,
              color: colors.gray700,
              lineHeight: 1,
            }}
          >
            {total === 0
              ? translate('Not')
              : `${stats?.percentage?.success || 0}%`}
          </strong>
          <span
            style={{
              fontSize: 9,
              color: colors.gray500,
              marginTop: -2,
            }}
          >
            {total === 0 ? translate('monitored') : translate('uptime')}
          </span>
        </div>
      </div>

      <Legend>
        <LegendItem color={total === 0 ? colors.gray300 : colors.teal500}>
          {total === 0
            ? translate('Not monitored')
            : `${stats?.percentage?.success || 0}%`}{' '}
          {total === 0 ? '' : translate('Success')}
        </LegendItem>
        <LegendItem color={total === 0 ? colors.gray300 : colors.red500}>
          {total === 0
            ? translate('Not monitored')
            : `${stats?.percentage?.failed || 0}%`}{' '}
          {total === 0 ? '' : translate('Failed')}
        </LegendItem>
        <LegendItem color={total === 0 ? colors.gray300 : colors.yellow400}>
          {total === 0
            ? translate('Not monitored')
            : `${stats?.percentage?.pending || 0}%`}{' '}
          {total === 0 ? '' : translate('Pending')}
        </LegendItem>
      </Legend>
    </StatsChartContainer>
  );
};

export const FormMonitor: React.FC = () => {
  const isPro = config.editions.isAtLeast(Edition.Pro);
  useSidebarSelect('freeform/form-monitor');

  const { data: forms, isFetching: isFetchingForms } = useQueryFormsWithStats();
  const { data: formIds, isFetching: isFetchingFormids } = useFMForms();
  const { data: formsWithStats = [], isFetching: isFetchingStats } =
    useFMFormStats();

  const isLoading =
    (!forms || !formIds) &&
    (isFetchingForms || isFetchingFormids || isFetchingStats);

  if (isLoading) {
    return (
      <FormMonitorWrapper>
        <FormMonitorLoader />
      </FormMonitorWrapper>
    );
  }

  if (!isPro) {
    return (
      <FormMonitorWrapper>
        <EmptyBlock
          lite
          title={translate(
            'Upgrade to the Freeform Pro edition to get access to Form Monitor'
          )}
        />
      </FormMonitorWrapper>
    );
  }

  if (formIds && formIds.length === 0) {
    return (
      <FormMonitorWrapper>
        <EmptyBlock lite title={translate('No forms are being monitored')} />
      </FormMonitorWrapper>
    );
  }

  return (
    <FormMonitorWrapper>
      {isLoading && <FormMonitorLoader />}
      {!isLoading && !isPro && (
        <EmptyBlock
          lite
          title={translate(
            'Upgrade to the Freeform Pro edition to get access to Form Monitor'
          )}
        />
      )}
      {!isLoading && isPro && formIds && formIds.length > 0 && (
        <Cards>
          {formIds.map((id) => {
            const form = forms?.find((form) => form.id === id);
            const formStats = formsWithStats?.find((f) => f.formId === id);

            if (!form) {
              return null;
            }

            const { name, settings } = form;
            const { color } = settings.general;

            return (
              <Card to={`${id}/tests`} key={id}>
                <CardContent>
                  <FormCardContent>
                    <Title>{name}</Title>
                  </FormCardContent>
                  <StatsChart stats={formStats?.stats} />
                </CardContent>
                <ResponsiveContainer width="100%" height={40}>
                  <AreaChart
                    data={form.chartData || randomData}
                    margin={{ top: 10, bottom: 3, left: 0, right: 0 }}
                  >
                    <defs>
                      <linearGradient
                        id={`color${form.id}`}
                        x1={0}
                        y1={0}
                        x2={0}
                        y2={1}
                      >
                        <stop offset="5%" stopColor={color} stopOpacity={0.4} />
                        <stop
                          offset="95%"
                          stopColor={color}
                          stopOpacity={0.3}
                        />
                      </linearGradient>
                    </defs>
                    <Area
                      type="monotone"
                      dataKey={'uv'}
                      stroke={color}
                      strokeWidth={1}
                      strokeOpacity={1}
                      fillOpacity={1}
                      fill={`url(#color${form.id})`}
                      isAnimationActive={false}
                    />
                  </AreaChart>
                </ResponsiveContainer>
                <PaddedChartFooter $color={color} />
              </Card>
            );
          })}
        </Cards>
      )}
    </FormMonitorWrapper>
  );
};
