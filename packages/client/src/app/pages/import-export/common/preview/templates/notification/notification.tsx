import React from 'react';
import { Checkbox } from '@components/elements/checkbox/checkbox';
import type { NotificationTemplate } from '@ff-client/app/pages/import-export/import/import.types';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import {
  BlockItem,
  Blocks,
  Directory,
  Label,
  ListItem,
  NotificationIcon,
  Spacer,
} from '../../preview.styles';

type Props = {
  templates: NotificationTemplate[];
  values: Array<string | number>;
  onUpdate: (values: Array<string | number>) => void;
};

export const Notification: React.FC<Props> = ({
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
            id="notification-templates-all"
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
        <Label htmlFor="notification-templates-all">
          {translate('Notification')}
        </Label>
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
                  id={`notification-template-${template.uid}`}
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
              <NotificationIcon />
              <Label $light htmlFor={`notification-template-${template.uid}`}>
                {template.name}
              </Label>
            </Blocks>
          </ListItem>
        ))}
      </ul>
    </>
  );
};
