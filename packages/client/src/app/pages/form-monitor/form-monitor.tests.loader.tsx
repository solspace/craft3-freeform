import React from 'react';
import Skeleton, { SkeletonTheme } from 'react-loading-skeleton';
import { colors } from '@ff-client/styles/variables';

import { TestTableStyled } from './form-monitor.tests.styles';

export const TestsLoader: React.FC = () => {
  return (
    <SkeletonTheme baseColor={colors.gray200} highlightColor={colors.gray200}>
      <TestTableStyled>
        <thead>
          <tr>
            <th>
              <Skeleton width={60} />
            </th>
            <th>
              <Skeleton width={100} />
            </th>
            <th>
              <Skeleton width={120} />
            </th>
            <th>
              <Skeleton width={80} />
            </th>
            <th>
              <Skeleton width={200} />
            </th>
          </tr>
        </thead>
        <tbody>
          {Array(10)
            .fill(0)
            .map((_, index) => (
              <tr key={index}>
                <td>
                  <Skeleton width={40} />
                </td>
                <td>
                  <Skeleton width={120} />
                </td>
                <td>
                  <Skeleton width={150} />
                </td>
                <td>
                  <Skeleton width={100} />
                </td>
                <td>
                  <Skeleton width={300} />
                </td>
              </tr>
            ))}
        </tbody>
      </TestTableStyled>
    </SkeletonTheme>
  );
};
