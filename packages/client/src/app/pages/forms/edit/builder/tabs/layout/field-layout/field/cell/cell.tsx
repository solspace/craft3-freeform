import React, { useMemo } from 'react';
import { useSelector } from 'react-redux';
import SpinnerIcon from '@components/loaders/spinner.svg';
import { useAppDispatch } from '@editor/store';
import { contextActions, FocusType } from '@editor/store/slices/context';
import { contextSelectors } from '@editor/store/slices/context/context.selectors';
import type { Field } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import { useFieldType } from '@ff-client/queries/field-types';
import { Type } from '@ff-client/types/fields';
import classes from '@ff-client/utils/classes';
import { hasErrors } from '@ff-client/utils/errors';

import { GroupFieldLayout } from '../../layout/group-field-layout/group-field-layout';

import { useLoaderAnimation } from './cell.animations';
import {
  FieldCellWrapper,
  Icon,
  Instructions,
  Label,
  LabelIcon,
  LabelText,
  Row,
} from './cell.styles';
import { FieldAssociationsBadges } from './cell-badges';
import { useFieldPreview } from './use-field-preview';

type Props = {
  field: Field;
};

export const FieldCell: React.FC<Props> = ({ field }) => {
  const dispatch = useAppDispatch();
  const type = useFieldType(field?.typeClass);
  const { uid } = field;

  const {
    active,
    type: contextType,
    uid: contextUid,
  } = useSelector(contextSelectors.focus);
  const noLabel = useMemo(
    () => type?.implements?.includes('noLabel') || false,
    [type]
  );
  const isActive = useMemo(() => {
    return active && contextType === FocusType.Field && contextUid === uid;
  }, [active, contextType, contextUid, uid]);

  const [preview, isLoadingPreview] = useFieldPreview(field, type);
  const [spinnerAnimation, iconAnimation] =
    useLoaderAnimation(isLoadingPreview);

  const { getTranslation } = useTranslations(field);

  if (field?.properties === undefined || !type) {
    return null;
  }

  const label = getTranslation('label', field.properties.label || type?.name);
  const instructions = getTranslation(
    'instructions',
    field.properties.instructions
  );

  return (
    <FieldCellWrapper
      className={classes(
        hasErrors(field.errors) && 'errors',
        type.type === Type.Group && 'group',
        isActive && 'active',
        'field'
      )}
      onClick={(event): void => {
        event.stopPropagation();
        dispatch(contextActions.setFocusedItem({ type: FocusType.Field, uid }));
      }}
    >
      {!noLabel && (
        <Label className="label">
          <LabelIcon>
            <Icon style={spinnerAnimation}>
              <SpinnerIcon />
            </Icon>
            <Icon
              style={iconAnimation}
              dangerouslySetInnerHTML={{ __html: type.icon }}
            />
          </LabelIcon>

          <LabelText>{label}</LabelText>

          {field.properties.required && <span className="required" />}

          <FieldAssociationsBadges uid={uid} />
        </Label>
      )}

      {instructions && <Instructions>{instructions}</Instructions>}

      {type.type === Type.Group && (
        <GroupFieldLayout field={field} layoutUid={field.properties?.layout} />
      )}

      {type.type !== Type.Group && (
        <>
          {noLabel ? (
            <Row>
              <div dangerouslySetInnerHTML={{ __html: preview }} />
              <FieldAssociationsBadges uid={uid} />
            </Row>
          ) : (
            <div dangerouslySetInnerHTML={{ __html: preview }} />
          )}
        </>
      )}
    </FieldCellWrapper>
  );
};
