import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Dropdown } from '@components/elements/custom-dropdown/dropdown';
import { PreviewEditor } from '@components/form-controls/preview/previewable-component.styles';
import type { CalculationProperty } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';
import type Tagify from '@yaireo/tagify';
import { MixedTags } from '@yaireo/tagify/dist/react.tagify.jsx';

import {
  CalculationBoxWrapper,
  PreviewTitle,
  TagMenu,
} from './calculation-box.editor.styles';
import { CalculationBoxHelp } from './calculation-box.help';
import {
  generateValue,
  useCalculationFieldHandles,
} from './calculation-box.hooks';

import '@yaireo/tagify/dist/tagify.css';

type Props = {
  value: string;
  property: CalculationProperty;
  updateValue: (value: string) => void;
};

type TagifyChangeEvent = CustomEvent<Tagify.ChangeEventData<Tagify.TagData>>;

export const CalculationBoxEditor: React.FC<Props> = ({
  value,
  property,
  updateValue,
}) => {
  const [calculationBoxValue, setCalculationBoxValue] = useState('');

  const handles = useCalculationFieldHandles(property);
  const tagifyRef = useRef<Tagify>(null);

  const onChange = useCallback((event: TagifyChangeEvent): void => {
    updateValue(
      event.detail.tagify.DOM.input.textContent
        .replace(/\u200B/g, '')
        .replace(/\s+/g, ' ')
        .trim()
    );
  }, []);

  const addTag = (value: string): void => {
    if (!value) {
      return;
    }

    const tagElm = tagifyRef.current.createTagElem({
      value,
    });

    tagifyRef.current.injectAtCaret(tagElm);
    const elm = tagifyRef.current.insertAfterTag(tagElm, '');
    tagifyRef.current.placeCaretAfterNode(elm);
  };

  useEffect(() => {
    setCalculationBoxValue(generateValue(value));
  }, []);

  return (
    <PreviewEditor>
      <PreviewTitle>
        <TagMenu>
          <Dropdown
            emptyOption={translate('Insert Field')}
            options={handles.map((handle) => ({
              value: handle,
              label: handle,
            }))}
            onChange={addTag}
            value=""
          />
        </TagMenu>

        <span>
          or type <mark>@</mark> to search on field handles
        </span>
      </PreviewTitle>
      <CalculationBoxWrapper>
        <MixedTags
          autoFocus={false}
          tagifyRef={tagifyRef}
          settings={{
            pattern: /@/,
            enforceWhitelist: true,
            editTags: false,
            pasteAsTags: true,
            duplicates: true,
            dropdown: {
              enabled: 0,
              includeSelectedTags: true,
            },
            templates: {
              tag: function (tagData) {
                return `
                <tag
                  title="${tagData.value}"
                  contenteditable="false"
                  spellcheck="false"
                  class="tagify__tag"
                  ${this.getAttributes(tagData)}
                >
                <x title="remove tag" class="tagify__tag__removeBtn"></x>
                  <div>
                    <p class="tagify__tag-text">
                      <span class="sr-only-value">field:</span>${
                        tagData.value
                      }</p>
                  </div>
                </tag>`;
              },
            },
            whitelist: handles,
          }}
          onChange={onChange}
          value={calculationBoxValue}
        />
      </CalculationBoxWrapper>
      <CalculationBoxHelp />
    </PreviewEditor>
  );
};
