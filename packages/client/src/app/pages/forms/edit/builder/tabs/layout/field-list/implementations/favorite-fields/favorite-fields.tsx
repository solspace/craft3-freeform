import React from 'react';
import { ErrorBlock } from '@components/notification-blocks/error/error-block';
import config from '@config/freeform/freeform.config';
import { useFetchFavorites } from '@ff-client/queries/field-favorites';
import { useFieldTypeSearch } from '@ff-client/queries/field-types';
import translate from '@ff-client/utils/translations';
import EditIcon from '@ff-icons/actions/edit.svg';

import { FieldGroup } from '../../field-group/field-group';
import { LoaderFieldGroup } from '../../field-group/field-group.loader';
import { List } from '../../field-group/field-group.styles';
import { useSelectSearchedFavorites } from '../../hooks/use-select-searched-fields';

import { useCreateModal } from './modal/use-create-modal';
import { FieldItem } from './field-item';

const title = translate('Favorites');

export const FavoriteFields: React.FC = () => {
  const select = useSelectSearchedFavorites();
  const { data, isFetching, isError, error } = useFetchFavorites({ select });
  const openModal = useCreateModal();
  const findType = useFieldTypeSearch();

  if (!data && isFetching) {
    return <LoaderFieldGroup words={[60]} items={2} />;
  }

  if (isError) {
    return <ErrorBlock>{error.message}</ErrorBlock>;
  }

  if (!data.length) {
    return null;
  }

  return (
    <FieldGroup
      title={translate(title)}
      button={
        config.limitations.can('layout.favoritesManager') && {
          icon: <EditIcon />,
          title: translate('Edit Favorites'),
          onClick: openModal,
        }
      }
    >
      <List>
        {data.map((favorite) => {
          const fieldType = findType(favorite.typeClass);
          if (!fieldType) {
            return null;
          }

          if (!fieldType?.visible) {
            return null;
          }

          return <FieldItem key={favorite.id} favorite={favorite} />;
        })}
      </List>
    </FieldGroup>
  );
};
