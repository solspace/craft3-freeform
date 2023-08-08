import React from 'react';
import { useSelector } from 'react-redux';
import type { Layout as LayoutType } from '@editor/builder/types/layout';
import { rowSelectors } from '@editor/store/slices/layout/rows/rows.selectors';
import translate from '@ff-client/utils/translations';

import { Row } from '../row/row';

import { useLayoutDrop } from './layout.drop';
import { DropZone, EmptyLayout, PageFieldLayoutWrapper } from './layout.styles';

type Props = {
  layout: LayoutType;
};

export const Layout: React.FC<Props> = ({ layout }) => {
  const rows = useSelector(rowSelectors.inLayout(layout));
  const { dropRef, placeholderAnimation } = useLayoutDrop(layout);

  return (
    <PageFieldLayoutWrapper ref={dropRef} className="field-layout">
      {!rows.length && (
        <EmptyLayout>
          Drag or click fields to add them to the layout
        </EmptyLayout>
      )}
      {rows.map((row) => (
        <Row row={row} key={row.uid} />
      ))}
      <DropZone style={placeholderAnimation}>
        {translate('+ insert row')}
      </DropZone>
    </PageFieldLayoutWrapper>
  );
};
