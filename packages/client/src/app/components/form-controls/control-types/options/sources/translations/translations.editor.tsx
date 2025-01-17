import React, { useRef } from 'react';
import {
  Cell,
  Input,
  Row,
  TableContainer,
  TabularOptions,
} from '@components/form-controls/control-types/table/table.editor.styles';
import { useCellNavigation } from '@components/form-controls/hooks/use-cell-navigation';
import { PreviewEditor } from '@components/form-controls/preview/previewable-component.styles';
import type { Field } from '@editor/store/slices/layout/fields';
import { useTranslations } from '@editor/store/slices/translations/translations.hooks';
import type { Property } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';
import { cloneDeep } from 'lodash';

import type { Option, OptionsConfiguration } from '../../options.types';

import { OriginalValuePreview } from './translations.editor.styles';
import type { OptionTranslations } from './translations.types';

type Props = {
  property: Property;
  value: OptionsConfiguration;
  field: Field;
};

export const OptionsTranslationsEditor: React.FC<Props> = ({
  value,
  property,
  field,
}) => {
  const { getTranslation, updateTranslation } = useTranslations(field);
  const options = (value.source === 'custom' && value.options) || [];

  const translation = getTranslation<OptionTranslations>(property.handle, {});
  const optionTranslations: Option[] = translation.options || [];

  const refs = useRef([]);
  refs.current = options.map(
    (_, index) => refs.current[index] || React.createRef<HTMLButtonElement>()
  );

  const { activeCell, setActiveCell, setCellRef, keyPressHandler } =
    useCellNavigation(options.length, 1);

  return (
    <PreviewEditor>
      <TableContainer>
        <TabularOptions>
          <tbody>
            {options.map((option, index) => (
              <Row key={index}>
                <Cell style={{ width: 200 }}>
                  <OriginalValuePreview className="code" title={option.value}>
                    {option.value || translate('Empty')}
                  </OriginalValuePreview>
                </Cell>

                <Cell>
                  <Input
                    type="text"
                    value={
                      optionTranslations.find(
                        (opt) => opt.value === option.value
                      )?.label || option.label
                    }
                    placeholder={translate('Label')}
                    autoFocus={activeCell === `${index}:0`}
                    ref={(element) => setCellRef(element, index, 0)}
                    onFocus={() => setActiveCell(index, 0)}
                    onKeyDown={keyPressHandler()}
                    onChange={(event) => {
                      const updatedOptions = cloneDeep(optionTranslations);
                      const translationIndex = updatedOptions.findIndex(
                        (opt) => opt.value === option.value
                      );

                      if (translationIndex === -1) {
                        updatedOptions.push({
                          value: option.value,
                          label: event.target.value,
                        });
                      } else {
                        updatedOptions[translationIndex].label =
                          event.target.value;
                      }

                      updateTranslation(property.handle, {
                        ...translation,
                        options: updatedOptions,
                      });
                    }}
                  />
                </Cell>
              </Row>
            ))}
          </tbody>
        </TabularOptions>
      </TableContainer>
    </PreviewEditor>
  );
};
