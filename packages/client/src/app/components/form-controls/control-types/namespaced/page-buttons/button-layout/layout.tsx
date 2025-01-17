import type { ReactNode } from 'react';
import React from 'react';
import { Control } from '@components/form-controls/control';
import type { ControlType } from '@components/form-controls/types';
import type { Page } from '@editor/builder/types/layout';
import type { PageButtonsLayoutProperty } from '@ff-client/types/properties';
import classes from '@ff-client/utils/classes';
import { isEqual } from 'lodash';

import BackIcon from './icons/back.svg';
import SaveIcon from './icons/save.svg';
import SubmitIcon from './icons/submit.svg';
import {
  Button,
  ButtonGroup,
  ButtonLayoutWrapper,
  LayoutBlock,
} from './layout.styles';

const icons: Record<string, ReactNode> = {
  save: <SaveIcon />,
  back: <BackIcon />,
  submit: <SubmitIcon />,
};

type ComponentType = ControlType<PageButtonsLayoutProperty, Page>;

const PageButtonLayout: React.FC<ComponentType> = ({
  value,
  property,
  errors,
  updateValue,
  context,
}) => {
  const { layouts } = property;
  const index = context.order;

  const buttonState: Record<string, boolean> = {
    save: context?.buttons?.save,
    back: context?.buttons?.back,
    submit: true,
  };

  const itemComparison: string[][][] = [];
  const items = layouts
    .map((layout) => {
      const groups = layout.split(' ').map((group) =>
        group
          .split('|')
          .filter((button) => buttonState.back || button !== 'back') // remove back button if not enabled
          .filter((button) => buttonState.save || button !== 'save') // remove save button if not enabled
          .filter((button) => index !== 0 || button !== 'back') // remove "back" from first page
          .filter(Boolean)
      );

      if (itemComparison.some((item) => isEqual(item, groups))) {
        return null;
      }

      itemComparison.push(groups);

      return {
        layout,
        groups,
      };
    })
    .filter(Boolean);

  return (
    <Control property={property} errors={errors}>
      <ButtonLayoutWrapper>
        {items.map((item, idx) => (
          <LayoutBlock
            key={idx}
            onClick={() => updateValue(item.layout)}
            className={classes(value === item.layout && 'active')}
          >
            {item.groups.map((buttons, groupIdx) => (
              <ButtonGroup key={groupIdx}>
                {buttons.map((button, buttonIdx) => (
                  <Button
                    className={classes(
                      button,
                      buttonState?.[button] && 'enabled'
                    )}
                    key={buttonIdx}
                  >
                    {icons[button]}
                  </Button>
                ))}
              </ButtonGroup>
            ))}
          </LayoutBlock>
        ))}
      </ButtonLayoutWrapper>
    </Control>
  );
};

export default PageButtonLayout;
