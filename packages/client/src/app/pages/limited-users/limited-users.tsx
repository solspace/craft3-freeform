import React from 'react';
import { Link } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { EmptyBlock } from '@components/empty-block/empty-block';
import { HeaderContainer } from '@components/layout/blocks/header-container';
import config, { Edition } from '@config/freeform/freeform.config';
import { useSidebarSelect } from '@ff-client/hooks/use-sidebar-select';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import {
  useLimitedUsersDeleteMutation,
  useLimitedUsersQuery,
} from './limited-users.queries';
import { SettingsSidebar } from './limited-users.sidebar';
import { ContentContainer } from './limited-users.styles';

export const LimitedUsers: React.FC = () => {
  const { data, isFetching } = useLimitedUsersQuery();
  const mutation = useLimitedUsersDeleteMutation();
  const isPro = config.editions.isAtLeast(Edition.Pro);
  const isCraft5 = config.metadata.craft.is5;

  useSidebarSelect(5);

  if (!data && isFetching) {
    return <div>Loading...</div>;
  }

  return (
    <div>
      <Breadcrumb
        id="settings"
        label={translate('Settings')}
        url="."
        external
      />
      <Breadcrumb
        id="limited-users"
        label={translate('Limited Users')}
        url="settings/limited-users"
      />

      <HeaderContainer
        extra={
          isPro && (
            <Link to="new" className="btn submit add icon">
              {translate('New Group')}
            </Link>
          )
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
          <div id="content" className="content-pane">
            {isPro && (
              <div className="tablepane">
                {data.length > 0 && (
                  <table className="data fullwidth">
                    <thead>
                      <tr>
                        <th>{translate('Name')}</th>
                        <th>{translate('Description')}</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      {data.map((item) => (
                        <tr key={item.id}>
                          <th>
                            <Link to={`${item.id}`}>{item.name}</Link>
                          </th>
                          <td>{item.description}</td>
                          <td className="thin">
                            <a
                              className="delete icon"
                              title={translate('Delete')}
                              onClick={() => {
                                if (
                                  confirm(
                                    translate(
                                      'Are you sure you want to delete this?'
                                    )
                                  )
                                ) {
                                  mutation.mutate(item.id);
                                }
                              }}
                            />
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                )}

                {data.length === 0 && (
                  <div style={{ padding: '100px 0 100px' }}>
                    <EmptyBlock
                      title={translate('No groups exist yet')}
                      subtitle={translate(
                        `Click on the "New Group" button to set up your first Limited User permission group.`
                      )}
                    />
                  </div>
                )}
              </div>
            )}

            {!isPro && (
              <EmptyBlock
                lite
                title={translate(
                  'Upgrade to the Freeform Pro edition to get access to the Limited Users feature'
                )}
              />
            )}
          </div>
        </ContentContainer>
      </div>
    </div>
  );
};
