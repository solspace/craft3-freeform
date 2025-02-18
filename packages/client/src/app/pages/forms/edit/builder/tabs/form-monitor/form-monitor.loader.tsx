import React from 'react';
import Skeleton, { SkeletonTheme } from 'react-loading-skeleton';
import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

const LoaderContainer = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.md};
  padding: ${spacings.md};
  background: ${colors.white};
  width: 100%;
`;

const ProgressLoader = styled.div`
  display: flex;
  flex-direction: column;
  gap: 6px;
`;

const HeaderLoader = styled.div`
  display: flex;
  justify-content: space-between;
  align-items: center;
`;

const DetailsLoaderContainer = styled(LoaderContainer)`
  padding: ${spacings.xl};
`;

const ChartLoaderContainer = styled.div`
  margin-bottom: ${spacings.xl};
`;

const TableLoaderContainer = styled.div`
  display: grid;
  grid-template-columns: 100px 150px 120px 1fr 120px;
  gap: ${spacings.md};
`;

export const FormMonitorLoader: React.FC = () => {
  return (
    <SkeletonTheme baseColor={colors.gray100} highlightColor={colors.gray200}>
      <LoaderContainer>
        <Skeleton width={100} height={20} />
        <Skeleton width={120} height={16} />

        {[...Array(3)].map((_, index) => (
          <ProgressLoader key={index}>
            <HeaderLoader>
              <Skeleton width={80} height={14} />
              <Skeleton width={60} height={14} />
            </HeaderLoader>
            <Skeleton width="100%" height={8} />
          </ProgressLoader>
        ))}
      </LoaderContainer>
    </SkeletonTheme>
  );
};

export const FormMonitorDetailsLoader: React.FC = () => {
  return (
    <SkeletonTheme baseColor={colors.gray100} highlightColor={colors.gray200}>
      <DetailsLoaderContainer>
        <ChartLoaderContainer>
          <Skeleton height={24} width={300} />
          <Skeleton height={100} />
        </ChartLoaderContainer>

        {/* Table Header */}
        <TableLoaderContainer>
          <Skeleton height={24} />
          <Skeleton height={24} />
          <Skeleton height={24} />
          <Skeleton height={24} />
          <Skeleton height={24} />
        </TableLoaderContainer>

        {/* Table Rows */}
        {[...Array(10)].map((_, index) => (
          <TableLoaderContainer key={index}>
            <Skeleton height={40} />
            <Skeleton height={40} />
            <Skeleton height={40} />
            <Skeleton height={40} />
            <Skeleton height={40} width={100} />
          </TableLoaderContainer>
        ))}
      </DetailsLoaderContainer>
    </SkeletonTheme>
  );
};
