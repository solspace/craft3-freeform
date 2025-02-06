import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { useQueryFormsWithStats } from '@ff-client/queries/forms';
import { scrollBar } from '@ff-client/styles/mixins';
import { borderRadius, colors, spacings } from '@ff-client/styles/variables';
import translate from '@ff-client/utils/translations';
import { format, parseISO } from 'date-fns';
import styled from 'styled-components';

import { FMEmptyTests } from './form-monitor.empty';
import { useFMFormTestsQuery } from './form-monitor.queries';
import {
  FormMonitorWrapper,
  StatusBadge,
  TestTable,
} from './form-monitor.styles';

const CodeBlock = styled.div`
  position: relative;

  padding: ${spacings.sm} ${spacings.md};

  font-family: monospace;

  background: ${colors.gray050};
  border: 1px solid ${colors.hairline};
  border-radius: ${borderRadius.lg};

  max-height: 60px;
  overflow-y: auto;

  ${scrollBar};
`;

export const FMTests: React.FC = () => {
  const { formId } = useParams();

  const { data: forms } = useQueryFormsWithStats();
  const { data, isFetching } = useFMFormTestsQuery(Number(formId));

  useSidebarSelect('freeform/form-monitor');

  return (
    <FormMonitorWrapper>
      {data === undefined && isFetching && <div>{translate('Loading...')}</div>}
      {data && data?.tests?.length === 0 && <FMEmptyTests />}
      {data !== undefined && data?.tests?.length > 0 && (
        <TestTable>
          <thead>
            <tr>
              <th>{translate('Test ID')}</th>
              <th>{translate('Date')}</th>
              <th>{translate('Form')}</th>
              <th>{translate('Status')}</th>
              <th>{translate('Response')}</th>
            </tr>
          </thead>
          <tbody>
            {data?.tests?.map((test) => {
              const form = forms.find((form) => form.id === test.formId);
              if (!form) {
                return null;
              }

              return (
                <tr key={test.id}>
                  <td className="no-break">#{test.id}</td>
                  <td className="no-break" title={test.dateCompleted}>
                    {format(
                      parseISO(test.dateCompleted || test.dateAttempted),
                      'do MMM yyyy'
                    )}
                  </td>
                  <td>
                    <Link to={`/forms/${form.id}`}>{form.name}</Link>
                  </td>
                  <td className="status-col no-break">
                    <StatusBadge className={`status-${test.status}`}>
                      {test.responseCode}
                    </StatusBadge>
                    <div>{test.status}</div>
                  </td>
                  <td className="code" title={test.response}>
                    {!!test.response && <CodeBlock>{test.response}</CodeBlock>}
                  </td>
                </tr>
              );
            })}
          </tbody>
        </TestTable>
      )}
    </FormMonitorWrapper>
  );
};
