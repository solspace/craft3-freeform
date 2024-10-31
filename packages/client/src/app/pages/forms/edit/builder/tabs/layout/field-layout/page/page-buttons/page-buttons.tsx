import React, { useMemo } from 'react';
import { useSelector } from 'react-redux';
import type { Page } from '@editor/builder/types/layout';
import { useAppDispatch } from '@editor/store';
import { contextActions, FocusType } from '@editor/store/slices/context';
import { contextSelectors } from '@editor/store/slices/context/context.selectors';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import classes from '@ff-client/utils/classes';

import { PageFieldLayoutWrapper } from '../../layout/layout.styles';

import { getButtonGroups } from './page-buttons.operations';
import { Button, ButtonGroup, ButtonGroupWrapper } from './page-buttons.styles';

type Props = {
  page: Page;
};

const buttonClasses: Record<string, string> = {
  back: 'btn',
  save: 'btn',
  submit: 'btn btn-submit',
};

export const PageButtons: React.FC<Props> = ({ page }) => {
  const dispatch = useAppDispatch();
  const { getTranslation } = useTranslations(page);

  const {
    active,
    type: contextType,
    uid: contextUid,
  } = useSelector(contextSelectors.focus);

  const isActive = useMemo(() => {
    return active && contextType === FocusType.Page && contextUid === page.uid;
  }, [active, contextType, contextUid, page.uid]);

  const buttonGroups = getButtonGroups(page);

  return (
    <PageFieldLayoutWrapper>
      <ButtonGroupWrapper
        className={classes(isActive && 'active')}
        onClick={() => {
          dispatch(
            contextActions.setFocusedItem({
              type: FocusType.Page,
              uid: page.uid,
            })
          );
        }}
      >
        {buttonGroups.map((group, index) => (
          <ButtonGroup key={index} className="page-buttons">
            {group.map((button, index) => (
              <Button
                className={buttonClasses[button?.handle]}
                key={index}
                type="button"
              >
                {getTranslation(`${button?.handle}Label`, button?.label)}
              </Button>
            ))}
          </ButtonGroup>
        ))}
      </ButtonGroupWrapper>
    </PageFieldLayoutWrapper>
  );
};
