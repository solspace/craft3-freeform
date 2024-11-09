import React from 'react';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import type { ExportOptions } from '../../export/export.types';
import type { FormImportData } from '../../import/import.types';

import { PreviewForms } from './forms/forms';
import { PreviewIntegrations } from './integrations/integrations';
import { PreviewSettings } from './settings/settings';
import { PreviewSubmissionsTemplates } from './submissions/submissions';
import { PreviewTemplates } from './templates/templates';
import { FileList, PreviewWrapper, SelectAll } from './preview.styles';

type Props = {
  data?: FormImportData;
  options: ExportOptions;
  disabled?: boolean;
  onUpdate: (options: ExportOptions) => void;
};

export const Preview: React.FC<Props> = ({
  data,
  options,
  disabled,
  onUpdate,
}) => {
  const isAllSelected =
    options.forms.length === data.forms?.length &&
    options.integrations.length === data.integrations?.length &&
    options.templates.pdf.length === data.templates.pdf?.length &&
    options.templates.notification.length ===
      data.templates.notification?.length &&
    options.templates.formatting.length === data.templates.formatting?.length &&
    options.templates.success.length === data.templates.success?.length &&
    options.formSubmissions.length === data.formSubmissions?.length &&
    options.settings;

  const emptyOptions: ExportOptions = {
    forms: [],
    templates: {
      pdf: [],
      notification: [],
      formatting: [],
      success: [],
    },
    integrations: [],
    formSubmissions: [],
    settings: false,
  };

  const filledOptions: ExportOptions = {
    forms: data.forms.map((form) => form.uid),
    templates: {
      pdf: data.templates.pdf.map((template) => template.uid),
      notification: data.templates.notification.map((template) => template.uid),
      formatting: data.templates.formatting.map(
        (template) => template.fileName
      ),
      success: data.templates.success.map((template) => template.fileName),
    },
    integrations: data.integrations.map((integration) => integration.uid),
    formSubmissions: data.formSubmissions.map(
      (submission) => submission.form.uid
    ),
    settings: true,
  };

  return (
    <PreviewWrapper className={classes(disabled && 'disabled')}>
      <FileList>
        <SelectAll
          onClick={() => {
            onUpdate(isAllSelected ? emptyOptions : filledOptions);
          }}
        >
          {translate(isAllSelected ? 'Deselect All' : 'Select All')}
        </SelectAll>

        <ul>
          <PreviewForms
            forms={data.forms}
            options={options.forms}
            onUpdate={(forms) => onUpdate({ ...options, forms })}
          />

          <PreviewTemplates
            templates={data.templates}
            options={options.templates}
            onUpdate={(templates) => onUpdate({ ...options, templates })}
          />

          <PreviewIntegrations
            integrations={data.integrations}
            options={options.integrations}
            onUpdate={(integrations) => onUpdate({ ...options, integrations })}
          />

          <PreviewSubmissionsTemplates
            submissions={data.formSubmissions}
            options={options.formSubmissions}
            onUpdate={(formSubmissions) =>
              onUpdate({ ...options, formSubmissions })
            }
          />

          {data.settings && (
            <PreviewSettings
              value={options.settings}
              onUpdate={(settings) => onUpdate({ ...options, settings })}
            />
          )}
        </ul>
      </FileList>
    </PreviewWrapper>
  );
};
