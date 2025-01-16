import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import String from '@components/form-controls/control-types/string/string';
import Textarea from '@components/form-controls/control-types/textarea/textarea';
import { HeaderContainer } from '@components/layout/blocks/header-container';
import { LoadingText } from '@components/loaders/loading-text/loading-text';
import config from '@config/freeform/freeform.config';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import { PropertyType } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import { notifications } from '@ff-client/utils/notifications';
import translate from '@ff-client/utils/translations';

import {
  useLimitedUsersMutation,
  useLimitedUsersSingleQuery,
} from './limited-users.queries';
import { SettingsSidebar } from './limited-users.sidebar';
import { ContentContainer, GroupWrapper, List } from './limited-users.styles';
import { ItemBlock } from './limited-users.sub-components';
import type { Item, RecursiveUpdate } from './limited-users.types';

export const LimitedUsersDetail: React.FC = () => {
  const { id } = useParams();
  const { data, isFetching } = useLimitedUsersSingleQuery(id);
  const navigate = useNavigate();

  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [state, setState] = useState([]);
  const mutation = useLimitedUsersMutation(id);
  const isCraft5 = config.metadata.craft.is5;

  useSidebarSelect('freeform/settings');

  useEffect(() => {
    if (data) {
      setName(data.name);
      setDescription(data.description);
      setState(data.items);
    }
  }, [data]);

  const updateValue: RecursiveUpdate = (id, updates): void => {
    const updateItem = (items: Item[], path?: string): Item[] => {
      return items.map((item) => {
        const currentPath = path ? `${path}.${item.id}` : item.id;
        if (currentPath === id) {
          return { ...item, ...updates };
        }

        if (item.children) {
          return {
            ...item,
            children: updateItem(item.children, currentPath),
          };
        }

        return item;
      });
    };

    setState((prev) => updateItem(prev));
  };

  if (!data && isFetching) {
    return <div>{translate('Loading...')}</div>;
  }

  return (
    <div>
      <Breadcrumb
        id="settings"
        label={translate('Settings')}
        url=".."
        external
      />
      <Breadcrumb
        id="limited-users"
        label={translate('Limited Users')}
        url="settings/limited-users"
      />
      <Breadcrumb
        id="limited-users-id"
        label={data?.name}
        url={`settings/limited-users/${id}`}
      />

      <HeaderContainer
        extra={
          <button
            className="btn submit"
            onClick={() =>
              mutation.mutate(
                { name, description, items: state },
                {
                  onSuccess: () => {
                    navigate(`/settings/limited-users`);
                    notifications.success(
                      translate('Permission saved successfully.')
                    );
                  },
                }
              )
            }
          >
            <LoadingText
              loading={mutation.isLoading}
              loadingText={translate('Saving')}
              spinner
            >
              {translate('Save')}
            </LoadingText>
          </button>
        }
      >
        {translate('Limited Users')}
      </HeaderContainer>

      <div id="main-content" className="has-sidebar">
        <SettingsSidebar />

        <ContentContainer
          id="content-container"
          className={classes(!isCraft5 && 'craft-4')}
        >
          <div id="content" className="content-pane" style={{ padding: 0 }}>
            <GroupWrapper>
              <String
                property={{
                  handle: 'name',
                  label: translate('Name'),
                  instructions: translate(
                    'Enter the name of the limited user permission.'
                  ),
                  type: PropertyType.String,
                }}
                value={name}
                updateValue={(value) => setName(value)}
              />

              <br />

              <Textarea
                property={{
                  handle: 'description',
                  label: translate('Description'),
                  instructions: translate(
                    'Enter a description for this permission.'
                  ),
                  type: PropertyType.Textarea,
                  rows: 4,
                  flags: [],
                }}
                value={description}
                updateValue={(value) => setDescription(value)}
              />

              <hr />

              <List>
                {state.map((item) => (
                  <ItemBlock
                    key={item.id}
                    item={item}
                    updateValue={updateValue}
                  />
                ))}
              </List>
            </GroupWrapper>
          </div>
        </ContentContainer>
      </div>
    </div>
  );
};
