import React from 'react';
import { ErrorBlock } from '@components/notification-blocks/error/error-block';
import config, { Edition } from '@config/freeform/freeform.config';
import { useFieldTypeSearch } from '@ff-client/queries/field-types';
import { useFetchGroups } from '@ff-client/queries/groups';
import type { GroupData } from '@ff-client/types/groups';
import translate from '@ff-client/utils/translations';
import EditIcon from '@ff-icons/actions/edit.svg';

import { FieldGroup } from '../../field-group/field-group';
import { LoaderFieldGroup } from '../../field-group/field-group.loader';
import { List } from '../../field-group/field-group.styles';
import { useSelectSearchedGroups } from '../../hooks/use-select-searched-fields';

import { useCreateModal } from './modal/use-create-modal';
import { GroupName, GroupWrapper } from './base-fields.styles';
import { FieldItem } from './field-item';

const title = translate('Field Types');

const Group: React.FC<{ group: GroupData }> = ({ group }) => {
  const findType = useFieldTypeSearch();
  const fields = group.types
    .map((type) => {
      const fieldType = findType(type);
      if (!fieldType?.visible) {
        return null;
      }

      return fieldType && <FieldItem key={type} fieldType={fieldType} />;
    })
    .filter(Boolean);

  if (!fields.length) {
    return null;
  }

  return (
    <GroupWrapper key={group.uid} color={group.color}>
      {group.label && <GroupName>{group.label}</GroupName>}
      <List>{fields}</List>
    </GroupWrapper>
  );
};

export const BaseFields: React.FC = () => {
  const select = useSelectSearchedGroups();
  const { data, isFetching, isError, error } = useFetchGroups({ select });
  const openModal = useCreateModal();

  if (!data && isFetching) {
    return <LoaderFieldGroup words={[50, 70]} items={16} />;
  }

  if (isError) {
    return <ErrorBlock>{error.message}</ErrorBlock>;
  }

  return (
    <FieldGroup
      button={
        config.limitations.can('layout.fieldManager') && {
          icon: <EditIcon />,
          title: translate('Edit Manager'),
          onClick: openModal,
        }
      }
      editionIsPro={config.editions.is(Edition.Pro)}
      title={translate(title)}
    >
      {data.groups.grouped?.map((group) => (
        <Group key={group.uid} group={group} />
      ))}

      {data?.types && <Group group={{ uid: 'external', types: data.types }} />}
    </FieldGroup>
  );
};
