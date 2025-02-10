import React from 'react';
import Skeleton, { SkeletonTheme } from 'react-loading-skeleton';
import { colors } from '@ff-client/styles/variables';

import {
  Cards,
  FormCardContent,
  LoaderCard,
  StatsChartContainer,
} from './form-monitor.styles';

export const FormMonitorLoader: React.FC = () => {
  return (
    <SkeletonTheme baseColor={colors.gray100} highlightColor={colors.gray200}>
      <Cards>
        {Array(3)
          .fill(0)
          .map((_, index) => (
            <LoaderCard key={index}>
              <FormCardContent>
                <Skeleton width={200} height={16} />
                <StatsChartContainer>
                  <div style={{ width: '100%', height: 100 }}>
                    <Skeleton height={100} />
                  </div>
                </StatsChartContainer>
              </FormCardContent>
            </LoaderCard>
          ))}
      </Cards>
    </SkeletonTheme>
  );
};
