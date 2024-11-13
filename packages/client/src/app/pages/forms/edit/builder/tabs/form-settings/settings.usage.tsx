import React from 'react';
import { useResolvedPath } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { EmptyBlock } from '@components/empty-block/empty-block';
import config from '@config/freeform/freeform.config';
import { useQueryFormUsage } from '@ff-client/queries/forms';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import NewsIcon from './empty.icon.svg';
import { FormSettingsContainer, SectionHeader } from './settings.editor.styles';

export const FormUsage: React.FC = () => {
  const { data, isFetching } = useQueryFormUsage();
  const currentPath = useResolvedPath('');
  const isCraft5 = config.metadata.craft.is5;

  if (!isCraft5) {
    return null;
  }

  return (
    <FormSettingsContainer>
      <Breadcrumb
        id="settings-usage"
        label={translate('Usage in Forms')}
        url={currentPath.pathname}
      />

      {!data && isFetching && <div>Loading...</div>}

      {!isFetching && data?.length === 0 && (
        <EmptyBlock
          title={translate("This form isn't used anywhere")}
          subtitle={translate(
            'If you add it to any elements, the elements will show here.'
          )}
          icon={<NewsIcon />}
          iconFade
        />
      )}

      {data?.length > 0 && (
        <>
          <SectionHeader>{translate('Usage in Forms')}</SectionHeader>

          <table className="data fullwidth collapsible">
            <thead>
              <tr>
                <th>{translate('Element')}</th>
                <th>{translate('Type')}</th>
                <th>{translate('Status')}</th>
              </tr>
            </thead>
            <tbody>
              {data.map((usage) => (
                <tr key={usage.id} className="element-row">
                  <th>
                    <div className="chip small element" data-id={usage.id}>
                      <div className="chip-content">
                        <span
                          className={classes(
                            'status',
                            usage.status.toLowerCase()
                          )}
                          role="img"
                        />

                        <a href={usage.url} className="label-link">
                          <span>{usage.title}</span>
                        </a>
                      </div>
                    </div>
                  </th>
                  <td>{usage.type}</td>
                  <td>{usage.status}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </>
      )}
    </FormSettingsContainer>
  );
};
