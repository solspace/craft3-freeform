import React from 'react';
import { useSelector } from 'react-redux';
import { useNavigate, useParams } from 'react-router-dom';
import { Sidebar } from '@components/layout/sidebar/sidebar';
import config from '@config/freeform/freeform.config';
import { SettingsOwnership } from '@editor/builder/tabs/form-settings/settings.ownership';
import { formSelectors } from '@editor/store/slices/form/form.selectors';
import { useQueryFormSettings } from '@ff-client/queries/forms';
import classes from '@ff-client/utils/classes';
import { hasErrors } from '@ff-client/utils/errors';
import translate from '@ff-client/utils/translations';

import { useLastTab } from '../tabs.hooks';

import NewsIcon from './news.icon.svg';
import { TAB_USAGE } from './settings';
import {
  SectionIcon,
  SectionLink,
  SectionWrapper,
} from './settings.sidebar.styles';

export const SettingsSidebar: React.FC = () => {
  const limitations = config.limitations;
  const navigate = useNavigate();
  const { setLastTab } = useLastTab('settings');
  const { sectionHandle } = useParams();

  const formErrors = useSelector(formSelectors.errors);

  const { data } = useQueryFormSettings();
  if (!data) {
    return null;
  }

  const sectionsWithErrors: string[] = [];

  data.forEach((namespace) => {
    namespace.properties.forEach((prop) => {
      if (hasErrors(formErrors?.[namespace.handle]?.[prop.handle])) {
        if (!sectionsWithErrors.includes(prop.section)) {
          sectionsWithErrors.push(prop.section);
        }
      }
    });
  });

  return (
    <Sidebar $lean>
      <SectionWrapper>
        {data.map((namespace) =>
          namespace.sections
            .filter((section) =>
              limitations.can(`settings.tab.${section.handle}`)
            )
            .map((section) => (
              <SectionLink
                key={section.handle}
                onClick={() => {
                  setLastTab(section.handle);
                  navigate(`${section.handle}`);
                }}
                className={classes(
                  sectionHandle === section.handle && 'active',
                  sectionsWithErrors.includes(section.handle) && 'errors'
                )}
              >
                <SectionIcon
                  dangerouslySetInnerHTML={{ __html: section.icon }}
                />
                {translate(section.label)}
              </SectionLink>
            ))
        )}

        <SectionLink
          onClick={() => {
            setLastTab(TAB_USAGE);
            navigate(TAB_USAGE);
          }}
          className={classes(sectionHandle === TAB_USAGE && 'active')}
        >
          <SectionIcon>
            <NewsIcon />
          </SectionIcon>
          {translate('Usage in Elements')}
        </SectionLink>
      </SectionWrapper>
      <SettingsOwnership />
    </Sidebar>
  );
};
