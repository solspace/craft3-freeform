import React from 'react';
import { Outlet, useLocation } from 'react-router-dom';
import { HeaderContainer } from '@components/layout/blocks/header-container';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { useQueryFormsWithStats } from '@ff-client/queries/forms';
import translate from '@ff-client/utils/translations';

import { Sidebar } from './common/sidebar/sidebar';
import { ImportExportWrapper } from './index.styles';

export const ImportExport: React.FC = () => {
  const { pathname } = useLocation();
  useSidebarSelect('export/profiles');
  useQueryFormsWithStats();

  let title: string;
  switch (pathname) {
    case '/import/express-forms':
      title = 'Import from Express Forms';
      break;

    case '/import/forms':
      title = 'Import Freeform Data (beta)';
      break;

    case '/export/forms':
      title = 'Export Freeform Data (beta)';
      break;
  }

  return (
    <div>
      <HeaderContainer>{translate(title)}</HeaderContainer>
      <ImportExportWrapper>
        <Sidebar />
        <Outlet />
      </ImportExportWrapper>
    </div>
  );
};
