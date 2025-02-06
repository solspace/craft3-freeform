import React from 'react';
import { EmptyBlock } from '@components/empty-block/empty-block';
import translate from '@ff-client/utils/translations';

import { FormMonitorWrapper } from './form-monitor.styles';

export const FMEmptyTests: React.FC = () => {
  return (
    <FormMonitorWrapper>
      <EmptyBlock lite title={translate('No form tests found')} />
    </FormMonitorWrapper>
  );
};
