import React from 'react';
import type * as ControlTypes from '@components/form-controls';
import { FormComponent } from '@components/form-controls';
import type { Page } from '@editor/builder/types/layout';
import { useAppDispatch } from '@editor/store';
import { pageActions } from '@editor/store/slices/layout/pages';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import type { GenericValue, Property } from '@ff-client/types/properties';

type Props = {
  property: Property;
  page: Page;
};

export const PageComponent: React.FC<Props> = ({ property, page }) => {
  const dispatch = useAppDispatch();
  const { getTranslation, updateTranslation } = useTranslations(page);

  const handle = property.handle;

  const updateValue: ControlTypes.UpdateValue<GenericValue> = (value) => {
    if (!updateTranslation(handle, value)) {
      dispatch(pageActions.editButtons({ uid: page.uid, key: handle, value }));
    }
  };

  const value = page.buttons?.[handle as keyof typeof page.buttons];
  const translatedValue =
    typeof value === 'string' ? getTranslation(handle, value) : value;

  return (
    <FormComponent
      value={translatedValue}
      property={property}
      updateValue={updateValue}
      context={page}
    />
  );
};
