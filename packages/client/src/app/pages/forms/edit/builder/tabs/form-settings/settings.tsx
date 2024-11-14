import React, { useEffect } from 'react';
import {
  Outlet,
  useNavigate,
  useParams,
  useResolvedPath,
} from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import config from '@config/freeform/freeform.config';
import { useQueryFormSettings } from '@ff-client/queries/forms';
import translate from '@ff-client/utils/translations';

import { useLastTab } from '../tabs.hooks';

import { LoaderFormSettings } from './settings.loader';
import { SettingsSidebar } from './settings.sidebar';
import { FormSettingsWrapper } from './settings.styles';

export const TAB_USAGE = 'usage';

export const FormSettings: React.FC = () => {
  const limitations = config.limitations;
  const { sectionHandle } = useParams();
  const navigate = useNavigate();
  const { lastTab, setLastTab } = useLastTab('settings');
  const currentPath = useResolvedPath('');

  const { data, isFetching } = useQueryFormSettings();

  useEffect(() => {
    if (lastTab) {
      navigate(lastTab);
    }
  }, []);

  useEffect(() => {
    if (!sectionHandle && !lastTab) {
      const firstSection = data?.[0]?.sections.filter((section) =>
        limitations.can(`settings.tab.${section.handle}`)
      )?.[0];
      if (firstSection) {
        setLastTab(firstSection.handle);
        navigate(`${firstSection.handle}`);
      }
    }
  }, [data, sectionHandle]);

  if (!data && isFetching) {
    return <LoaderFormSettings />;
  }

  return (
    <FormSettingsWrapper>
      <Breadcrumb
        id="settings"
        label={translate('Settings')}
        url={currentPath.pathname}
      />
      <SettingsSidebar />
      <Outlet />
    </FormSettingsWrapper>
  );
};
