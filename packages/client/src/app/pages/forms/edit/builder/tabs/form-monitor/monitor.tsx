import React from 'react';
import { Outlet } from 'react-router-dom';

import { List } from './sidebar/list';
import { MonitorWrapper } from './monitor.styles';

export const FormMonitor: React.FC = () => {
  return (
    <MonitorWrapper>
      <List />
      <Outlet />
    </MonitorWrapper>
  );
};
