import React from 'react';
import { useParams } from 'react-router-dom';
import { Sidebar } from '@ff-client/app/components/layout/sidebar/sidebar';
import { useFMFormTestsQuery } from '@ff-client/queries/form-monitor';
import translate from '@ff-client/utils/translations';

import { FormMonitorLoader } from '../form-monitor.loader';

import {
  ChartContainer,
  Progress,
  ProgressBar,
  StatContainer,
  StatHeader,
  StatLabel,
  StatRow,
  StatValue,
  TotalCount,
  Wrapper,
} from './list.styles';

export const List: React.FC = () => {
  const { formId } = useParams();
  const { data: formTests, isLoading } = useFMFormTestsQuery(Number(formId));

  if (isLoading) {
    return (
      <Sidebar>
        <FormMonitorLoader />
      </Sidebar>
    );
  }

  if (!formTests) return null;

  const { stats } = formTests;

  return (
    <Sidebar>
      <Wrapper>
        <h3>{translate('Results')}</h3>
        <ChartContainer>
          <StatContainer>
            <TotalCount>
              {translate('Total Tests')}: {stats.total}
            </TotalCount>
            <StatRow>
              <StatHeader>
                <StatLabel $type="success">{translate('Success')}</StatLabel>
                <StatValue>
                  {stats.percentage.success}% ({stats.success})
                </StatValue>
              </StatHeader>
              <ProgressBar>
                <Progress
                  $type="success"
                  $percentage={stats.percentage.success}
                />
              </ProgressBar>
            </StatRow>

            <StatRow>
              <StatHeader>
                <StatLabel $type="failed">{translate('Failed')}</StatLabel>
                <StatValue>
                  {stats.percentage.failed}% ({stats.failed})
                </StatValue>
              </StatHeader>
              <ProgressBar>
                <Progress
                  $type="failed"
                  $percentage={stats.percentage.failed}
                />
              </ProgressBar>
            </StatRow>

            <StatRow>
              <StatHeader>
                <StatLabel $type="pending">{translate('Pending')}</StatLabel>
                <StatValue>
                  {stats.percentage.pending}% ({stats.pending})
                </StatValue>
              </StatHeader>
              <ProgressBar>
                <Progress
                  $type="pending"
                  $percentage={stats.percentage.pending}
                />
              </ProgressBar>
            </StatRow>
          </StatContainer>
        </ChartContainer>
      </Wrapper>
    </Sidebar>
  );
};
