import React from 'react';
import { Control } from '@components/form-controls/control';
import { PreviewableComponent } from '@components/form-controls/preview/previewable-component';
import type { ControlType } from '@components/form-controls/types';
import type { WYSIWYGProperty } from '@ff-client/types/properties';

import { WysiwygEditor } from './wysiwyg.editor';
import { WysiwygPreview } from './wysiwyg.preview';

const Wysiwyg: React.FC<ControlType<WYSIWYGProperty>> = ({
  value,
  property,
  errors,
  updateValue,
  context,
}) => {
  return (
    <Control property={property} errors={errors} context={context}>
      <PreviewableComponent preview={<WysiwygPreview value={value} />}>
        <WysiwygEditor value={value} updateValue={updateValue} />
      </PreviewableComponent>
    </Control>
  );
};

export default Wysiwyg;
