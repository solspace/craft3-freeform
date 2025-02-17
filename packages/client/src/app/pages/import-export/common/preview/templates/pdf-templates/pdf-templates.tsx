import React from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import type { PdfTemplate } from '@ff-client/app/pages/import-export/import/import.types';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import {
  BlockItem,
  Blocks,
  Directory,
  Label,
  ListItem,
  PdfTemplateIcon,
  Spacer,
} from '../../preview.styles';

type Props = {
  templates: PdfTemplate[];
  values: string[];
  onUpdate: (values: string[]) => void;
};

export const PdfTemplates: React.FC<Props> = ({
  templates,
  values,
  onUpdate,
}) => {
  if (!templates.length) {
    return null;
  }

  return (
    <>
      <Blocks>
        <BlockItem>
          <Checkbox
            id="pdf-templates-all"
            checked={templates.length === values.length}
            onChange={() =>
              onUpdate(
                templates.length === values.length
                  ? []
                  : templates.map((template) => template.uid)
              )
            }
          />
        </BlockItem>
        <Spacer $dash />
        <Directory />
        <Label htmlFor="pdf-templates-all">{translate('PDF')}</Label>
      </Blocks>
      <ul>
        {templates.map((template) => (
          <ListItem
            key={template.uid}
            className={classes(
              'selectable',
              values.includes(template.uid) && 'selected'
            )}
          >
            <Blocks>
              <BlockItem>
                <Checkbox
                  id={`pdf-template-${template.uid}`}
                  checked={values.includes(template.uid)}
                  onChange={() =>
                    onUpdate(
                      values.includes(template.uid)
                        ? values.filter((id) => id !== template.uid)
                        : [...values, template.uid]
                    )
                  }
                />
              </BlockItem>
              <Spacer $dash $width={2} />
              <PdfTemplateIcon />
              <Label $light htmlFor={`pdf-template-${template.uid}`}>
                {template.name}
              </Label>
            </Blocks>
          </ListItem>
        ))}
      </ul>
    </>
  );
};
