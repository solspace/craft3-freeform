import React from 'react';
import { EmptyBlock } from '@components/empty-block/empty-block';
import translate from '@ff-client/utils/translations';

import { FormMonitorWrapper } from './form-monitor.styles';

interface FMEmptyTestsProps {
  error?: boolean;
}

export const FMEmptyTests: React.FC<FMEmptyTestsProps> = ({ error }) => {
  return (
    <FormMonitorWrapper>
      <EmptyBlock
        lite
        title={
          error ? translate('Error loading tests') : translate('No tests found')
        }
      />
    </FormMonitorWrapper>
  );
};
