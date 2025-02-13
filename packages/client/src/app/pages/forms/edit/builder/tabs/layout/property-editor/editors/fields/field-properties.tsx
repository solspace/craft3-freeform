import React from 'react';
import Skeleton from 'react-loading-skeleton';
import { useSelector } from 'react-redux';
import config from '@config/freeform/freeform.config';
import { useAppDispatch } from '@editor/store';
import { contextActions } from '@editor/store/slices/context';
import { fieldSelectors } from '@editor/store/slices/layout/fields/fields.selectors';
import CloseIcon from '@ff-client/assets/icons/circle-xmark-solid.svg';
import {
  useFetchFieldPropertySections,
  useFieldType,
} from '@ff-client/queries/field-types';
import { type Property } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import { CloseLink, Icon, Title } from '../../property-editor.styles';
import { SectionBlock } from '../../section-block';
import { SectionWrapper } from '../../section-block.styles';

import { FavoriteButton } from './favorite/favorite.button';
import { FieldComponent } from './field-component';
import { FieldPropertiesWrapper } from './field-properties.styles';

const sectionFilter = (handle: string) => (property: Property) =>
  property.section === handle;

export const FieldProperties: React.FC<{ uid: string }> = ({ uid }) => {
  const dispatch = useAppDispatch();

  const { data: sections, isFetching } = useFetchFieldPropertySections();

  const field = useSelector(fieldSelectors.one(uid));
  const type = useFieldType(field?.typeClass);

  if (!field || !type) {
    return <FieldPropertiesWrapper />;
  }

  if (!sections && isFetching) {
    return (
      <FieldPropertiesWrapper>
        <Title>
          <Icon dangerouslySetInnerHTML={{ __html: type.icon }} />
          <span>{translate(type.name)}</span>
        </Title>
        <SectionWrapper>
          <Skeleton />
        </SectionWrapper>
      </FieldPropertiesWrapper>
    );
  }

  const sectionBlocks: React.ReactElement[] = [];
  sections
    .sort((a, b) => a.order - b.order)
    .forEach(({ handle, label, icon }, sectionIndex) => {
      const properties = type.properties
        .filter(sectionFilter(handle))
        .filter((property) => property.visible);
      if (!properties.length) {
        return;
      }

      sectionBlocks.push(
        <SectionBlock label={translate(label)} icon={icon} key={handle}>
          {properties.map((property, propertyIndex) => (
            <FieldComponent
              autoFocus={sectionIndex === 0 && propertyIndex === 0}
              key={property.handle}
              field={field}
              property={property}
            />
          ))}
        </SectionBlock>
      );
    });

  return (
    <FieldPropertiesWrapper>
      <CloseLink onClick={() => dispatch(contextActions.unfocus())}>
        <CloseIcon />
      </CloseLink>
      {config.limitations.can('layout.favorite') && (
        <FavoriteButton field={field} />
      )}
      <Title>
        <Icon dangerouslySetInnerHTML={{ __html: type.icon }} />
        <span>{translate(type.name)}</span>
      </Title>
      <SectionWrapper>{sectionBlocks}</SectionWrapper>
    </FieldPropertiesWrapper>
  );
};
