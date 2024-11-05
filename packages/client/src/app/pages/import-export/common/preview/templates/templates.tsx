import React from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import type {
  TemplateCollection,
  TemplateValues,
} from '@ff-client/app/pages/import-export/import/import.types';
import translate from '@ff-client/utils/translations';

import {
  BlockItem,
  Blocks,
  Directory,
  FormattingIcon,
  Label,
  ListItem,
  SuccessIcon,
} from '../preview.styles';

import { FileTemplates } from './file-templates/file-templates';
import { Notification } from './notification/notification';
import { PdfTemplates } from './pdf-templates/pdf-templates';

type Props = {
  templates: TemplateCollection;
  options: TemplateValues;
  onUpdate: (templates: TemplateValues) => void;
};

const isAllChecked = (
  templates: TemplateCollection,
  values: TemplateValues
): boolean =>
  values.pdf.length === templates.pdf.length &&
  values.notification.length === templates.notification.length &&
  values.formatting.length === templates.formatting.length &&
  values.success.length === templates.success.length;

export const PreviewTemplates: React.FC<Props> = ({
  templates,
  options,
  onUpdate,
}) => {
  if (
    !templates.pdf.length &&
    !templates.notification.length &&
    !templates.formatting.length &&
    !templates.success.length
  ) {
    return null;
  }

  return (
    <ListItem>
      <Blocks>
        <BlockItem>
          <Checkbox
            id="templates-all"
            checked={isAllChecked(templates, options)}
            onChange={() =>
              isAllChecked(templates, options)
                ? onUpdate({
                    pdf: [],
                    notification: [],
                    formatting: [],
                    success: [],
                  })
                : onUpdate({
                    pdf: templates.pdf.map((template) => template.uid),
                    notification: templates.notification.map(
                      (template) => template.uid
                    ),
                    formatting: templates.formatting.map(
                      (template) => template.fileName
                    ),
                    success: templates.success.map(
                      (template) => template.fileName
                    ),
                  })
            }
          />
        </BlockItem>
        <Directory />
        <Label htmlFor="templates-all">{translate('Templates')}</Label>
      </Blocks>

      <PdfTemplates
        templates={templates.pdf}
        values={options.pdf}
        onUpdate={(values) => onUpdate({ ...options, pdf: values })}
      />

      <Notification
        templates={templates.notification}
        values={options.notification}
        onUpdate={(values) => onUpdate({ ...options, notification: values })}
      />

      <FileTemplates
        groupTitle={translate('Formatting')}
        icon={<FormattingIcon />}
        templates={templates.formatting}
        values={options.formatting}
        onUpdate={(values) => onUpdate({ ...options, formatting: values })}
      />

      <FileTemplates
        groupTitle={translate('Success')}
        icon={<SuccessIcon />}
        templates={templates.success}
        values={options.success}
        onUpdate={(values) => onUpdate({ ...options, success: values })}
      />
    </ListItem>
  );
};
