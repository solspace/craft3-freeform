import React from 'react';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import config from '@config/freeform/freeform.config';
import { useLocalStorage } from '@ff-client/hooks/ts-hooks/use-local-storage';
import {
  fetchFieldPropertySections,
  fetchFieldTypes,
  QKFieldTypes,
} from '@ff-client/queries/field-types';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';
import { useQueryClient } from '@tanstack/react-query';

import { useCreateFormModal } from './modals/hooks/use-create-form-modal';
import { FormGrid } from './views/grid/grid';
import { GridSites } from './views/grid/grid.sites';
import { FormList } from './views/list/list';
import { Button, Header, Title, ViewButtons } from './list-view.styles';

enum View {
  List,
  Grid,
}

export const ListProvider: React.FC = () => {
  const queryClient = useQueryClient();
  const openCreateFormModal = useCreateFormModal();

  const [view, setView] = useLocalStorage('forms-list-view', View.Grid);
  const isCraft5 = config.metadata.craft.is5;
  const { canCreate } = config.metadata.freeform;

  queryClient.prefetchQuery(QKFieldTypes.all, fetchFieldTypes);
  queryClient.prefetchQuery(
    QKFieldTypes.propertySections(),
    fetchFieldPropertySections
  );

  return (
    <>
      <Breadcrumb id="form-list" label="Forms" url="/forms" />

      <Header>
        <Title>{translate('Forms')}</Title>

        <GridSites />

        <ViewButtons className="btngroup btngroup--exclusive">
          <button
            type="button"
            className={classes('btn', View.List === view && 'active')}
            data-icon="list"
            aria-label="Display in a table"
            title={translate('Display as list')}
            onClick={() => setView(View.List)}
          />
          <button
            type="button"
            className={classes('btn', View.Grid === view && 'active')}
            data-icon={classes(isCraft5 ? 'element-cards' : 'grid')}
            title={translate('Display as cards')}
            onClick={() => setView(View.Grid)}
          />
        </ViewButtons>

        {canCreate && (
          <Button className="btn submit add icon" onClick={openCreateFormModal}>
            {translate('Add new Form')}
          </Button>
        )}
      </Header>

      {view === View.List && <FormList />}
      {view === View.Grid && <FormGrid />}
    </>
  );
};
