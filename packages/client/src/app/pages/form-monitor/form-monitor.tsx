/* eslint-disable react/display-name */
import React from 'react';
import { Link } from 'react-router-dom';
import { EmptyBlock } from '@components/empty-block/empty-block';
import config, { Edition } from '@config/freeform/freeform.config';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { useQueryFormsWithStats } from '@ff-client/queries/forms';
import translate from '@ff-client/utils/translations';

import { useFMForms } from './form-monitor.queries';
import { FormMonitorWrapper } from './form-monitor.styles';

export const FormMonitor: React.FC = () => {
  const isPro = config.editions.isAtLeast(Edition.Pro);
  useSidebarSelect(5);

  const { data: forms, isFetching: isFetchingForms } = useQueryFormsWithStats();
  const { data: formIds, isFetching: isFetchingFormids } = useFMForms();

  const isLoading =
    (!forms || !formIds) && (isFetchingForms || isFetchingFormids);

  if (!isPro) {
    return (
      <FormMonitorWrapper>
        <EmptyBlock
          lite
          title={translate(
            'Upgrade to the Freeform Pro edition to get access to Form Monitor'
          )}
        />
      </FormMonitorWrapper>
    );
  }

  if (isLoading) {
    return (
      <FormMonitorWrapper>
        <div>{translate('Loading...')}</div>
      </FormMonitorWrapper>
    );
  }

  return (
    <FormMonitorWrapper>
      <ul>
        {formIds.map((id) => {
          const form = forms.find((form) => form.id === id);
          if (!form) {
            return null;
          }

          return (
            <li key={id}>
              <Link to={`${id}/tests`}>{form.name}</Link>
            </li>
          );
        })}
      </ul>
    </FormMonitorWrapper>
  );
};
