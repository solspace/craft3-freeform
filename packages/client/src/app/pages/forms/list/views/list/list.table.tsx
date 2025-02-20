import React from 'react';
import config from '@config/freeform/freeform.config';
import type { FormWithStats } from '@ff-client/types/forms';
import translate from '@ff-client/utils/translations';

import { useCreateFormModal } from '../../modals/hooks/use-create-form-modal';

import { ListTableRow } from './list.table.row';
import { ListTableRowLoading } from './list.table.row.loading';
import { Table } from './list.table.styles';

type Props = {
  forms: FormWithStats[] | undefined;
  isFetching?: boolean;
};

export const ListTable: React.FC<Props> = ({ forms, isFetching }) => {
  const openCreateFormModal = useCreateFormModal();
  const { canCreate } = config.metadata.freeform;

  const hasFormMonitor = forms?.some((form) => form.formMonitor?.enabled);

  return (
    <Table className="table data">
      <thead>
        <tr>
          <th>{translate('Name')}</th>
          <th>{translate('Handle')}</th>
          <th>{translate('Description')}</th>
          <th>{translate('Chart')}</th>
          {hasFormMonitor && <th>{translate('Form Monitor')}</th>}
          <th>{translate('Submissions')}</th>
          <th>{translate('Spam')}</th>
          <th>{translate('Manage')}</th>
        </tr>
      </thead>
      <tbody>
        {isFetching && forms === undefined && (
          <>
            <ListTableRowLoading />
            <ListTableRowLoading />
            <ListTableRowLoading />
            <ListTableRowLoading />
          </>
        )}

        {!isFetching && !forms?.length && canCreate && (
          <tr>
            <td colSpan={hasFormMonitor ? 8 : 7}>
              <p>
                {translate(
                  `You don't have any forms yet. Create your first form now...`
                )}
              </p>

              <button
                className="btn submit add icon"
                onClick={openCreateFormModal}
              >
                {translate('New Form')}
              </button>
            </td>
          </tr>
        )}

        {!isFetching && !forms?.length && !canCreate && (
          <tr>
            <td colSpan={hasFormMonitor ? 8 : 7}>
              <p>{translate(`You don't have any forms yet.`)}</p>
            </td>
          </tr>
        )}

        {forms
          ?.sort((a, b) => a.name.localeCompare(b.name))
          ?.map((form) => (
            <ListTableRow
              key={form.id}
              form={form}
              hasFormMonitor={hasFormMonitor}
            />
          ))}
      </tbody>
    </Table>
  );
};
