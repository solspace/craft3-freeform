import React from 'react';
import { colors } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import { Legend } from 'recharts';
import { Cell, Pie, PieChart } from 'recharts';

import { LegendItem, StatsChartContainer } from './card.monitor.stats.styles';

export const StatsChart: React.FC<{
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
      <div style={{ width: 60, height: 60, position: 'relative' }}>
        <PieChart width={60} height={60}>
          <Pie
            data={data}
            cx={30}
            cy={30}
            innerRadius={20}
            outerRadius={25}
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
            {total === 0 ? translate('audited') : translate('uptime')}
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
