import React from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import type { FileTemplate } from '@ff-client/app/pages/import-export/import/import.types';
import classes from '@ff-client/utils/classes';
import { kebabCase } from 'lodash';

import {
  BlockItem,
  Blocks,
  Directory,
  Label,
  ListItem,
  Spacer,
} from '../../preview.styles';

type Props = {
  groupTitle: string;
  icon: JSX.Element;
  templates: FileTemplate[];
  values: string[];
  onUpdate: (values: string[]) => void;
};

export const FileTemplates: React.FC<Props> = ({
  groupTitle,
  icon,
  templates,
  values,
  onUpdate,
}) => {
  const handle = kebabCase(groupTitle);

  if (!templates.length) {
    return null;
  }

  return (
    <>
      <Blocks>
        <BlockItem>
          <Checkbox
            id={`${handle}-templates-all`}
            checked={templates.length === values.length}
            onChange={() =>
              onUpdate(
                templates.length === values.length
                  ? []
                  : templates.map((template) => template.fileName)
              )
            }
          />
        </BlockItem>
        <Spacer $dash />
        <Directory />
        <Label htmlFor={`${handle}-templates-all`}>{groupTitle}</Label>
      </Blocks>
      <ul>
        {templates.map((template) => (
          <ListItem
            key={template.fileName}
            className={classes(
              'selectable',
              values.includes(template.fileName) && 'selected'
            )}
          >
            <Blocks>
              <BlockItem>
                <Checkbox
                  id={`file-template-${handle}-${template.fileName}`}
                  checked={values.includes(template.fileName)}
                  onChange={() =>
                    onUpdate(
                      values.includes(template.fileName)
                        ? values.filter(
                            (fileName) => fileName !== template.fileName
                          )
                        : [...values, template.fileName]
                    )
                  }
                />
              </BlockItem>
              <Spacer $dash $width={2} />
              {icon}
              <Label
                $light
                htmlFor={`file-template-${handle}-${template.fileName}`}
              >
                {template.name}
              </Label>
            </Blocks>
          </ListItem>
        ))}
      </ul>
    </>
  );
};
