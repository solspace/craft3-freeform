import React, { useEffect, useState } from 'react';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import String from '@components/form-controls/control-types/string/string';
import { ContentContainer } from '@components/layout/blocks/content-container';
import { Field } from '@components/layout/blocks/field';
import { LoadingText } from '@components/loaders/loading-text/loading-text';
import { Preview } from '@ff-client/app/pages/import-export/common/preview/preview';
import { Progress } from '@ff-client/app/pages/import-export/common/progress/progress';
import { useProgressEvent } from '@ff-client/app/pages/import-export/common/progress/progress.hooks';
import { PropertyType } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import { downloadFile } from '@ff-client/utils/files';
import translate from '@ff-client/utils/translations';
import { generateUrl } from '@ff-client/utils/urls';
import axios from 'axios';

import type { ExportOptions } from '../../export.types';

import { useFormsDataQuery, useFormsExportMutation } from './freeform.queries';

export const ExportFreeform: React.FC = () => {
  const progressEvent = useProgressEvent();
  const {
    attachListener,
    triggerProgress,
    clearProgress,
    progress: { active: progressActive },
  } = progressEvent;

  const { data, isFetching } = useFormsDataQuery();
  const { mutate, isLoading } = useFormsExportMutation({
    onSuccess: (res) => {
      const token = res.data.token;
      triggerProgress(generateUrl(`/api/export?token=${token}`));
    },
  });

  const [active] = useState(false);
  const [options, setOptions] = useState<ExportOptions>({
    forms: [],
    formSubmissions: [],
    templates: {
      pdf: [],
      notification: [],
      formatting: [],
      success: [],
    },
    integrations: [],
    settings: false,
    password: '',
  });

  useEffect(() => {
    attachListener('file-token', async (event) => {
      const token = event.data;
      const url = generateUrl(`/api/export/download?token=${token}`);

      const res = await axios.get(url, { responseType: 'blob' });

      const time = new Date()
        .toISOString()
        .replace(/[-:]/g, '')
        .replace('T', '-')
        .slice(0, -5);

      const name = `freeform-export-${time}.zip`;

      downloadFile(res.data, name);
    });
  }, []);

  const onClick = (): void => {
    clearProgress();
    mutate(options);
  };

  const isCurrentlyActive = isFetching || active || progressActive || isLoading;

  if (isFetching) {
    return <ContentContainer>{translate('Loading...')}</ContentContainer>;
  }

  return (
    <ContentContainer>
      <Breadcrumb id="export" label="Export" url="export/forms" />
      <Breadcrumb id="export-forms" label="Freeform Data" url="export/forms" />

      {data && (
        <Field
          label={translate('Select Data to Export')}
          instructions={translate(
            'Choose which Freeform data to include in the export. If you export submissions without the corresponding form, the submissions will not be included.'
          )}
        >
          <Preview
            disabled={false}
            data={data}
            options={options}
            onUpdate={(options) => setOptions(options)}
          />
        </Field>
      )}

      <String
        value={options.password || ''}
        updateValue={(password) => setOptions({ ...options, password })}
        property={{
          handle: 'password',
          label: 'Password-protect the Export File (optional)',
          instructions:
            'Enter a password if you want to protect your zip file with a password.',
          type: PropertyType.String,
          placeholder: 'Enter a password',
        }}
      />

      <div className="field">
        <button
          className={classes(
            'btn',
            'submit',
            isCurrentlyActive && 'disabled',
            !options.forms.length &&
              !options.templates.notification.length &&
              !options.templates.formatting.length &&
              !options.templates.success.length &&
              !options.integrations.length &&
              !options.formSubmissions.length &&
              !options.settings &&
              'disabled'
          )}
          disabled={isCurrentlyActive}
          onClick={onClick}
        >
          <LoadingText
            loadingText={translate('Exporting...')}
            loading={isCurrentlyActive}
            spinner
          >
            {translate('Begin Export')}
          </LoadingText>
        </button>
      </div>

      <Progress
        label={translate('Export Progress')}
        finishLabel={translate('Export completed successfully!')}
        event={progressEvent}
      />
    </ContentContainer>
  );
};
