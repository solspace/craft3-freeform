import React from 'react';
import { Dropdown } from '@components/elements/custom-dropdown/dropdown';
import { HelpText } from '@components/elements/help-text';
import {
  type FieldMapping,
  TargetFieldType,
} from '@ff-client/types/integrations';
import classes from '@ff-client/utils/classes';
import translate from '@ff-client/utils/translations';

import CustomIcon from './icons/custom.svg';
import ListIcon from './icons/list.svg';
import RelationIcon from './icons/relation.svg';
import { FieldSelect } from './field-select';
import {
  MappingContainer,
  MappingWrapper,
  SourceField,
  TwigInput,
  TypeButton,
  TypeButtonGroup,
} from './mapping.styles';
import type { SourceField as SourceFieldType } from './mapping.types';

type Props = {
  sources: SourceFieldType[];
  mapping?: FieldMapping;
  updateValue: (value: FieldMapping) => void;
};

export const FieldMappingController: React.FC<Props> = ({
  sources,
  mapping,
  updateValue,
}) => {
  if (!mapping) {
    return null;
  }

  const update = (
    sourceId: string | number,
    type: TargetFieldType,
    value?: string
  ): void => {
    updateValue({
      ...mapping,
      [sourceId]: {
        type,
        value,
      },
    });
  };

  return (
    <MappingContainer>
      {sources.length === 0 && (
        <HelpText>{translate('No data present')}</HelpText>
      )}
      {sources.map((source) => {
        const map = mapping[source.id] ?? {
          type: TargetFieldType.Relation,
          value: '',
        };

        return (
          <MappingWrapper key={source.id}>
            <SourceField className={classes(source.required && 'required')}>
              <span>{source.label}</span>
            </SourceField>

            <TypeButtonGroup>
              {source.options?.length > 0 && (
                <TypeButton
                  title={translate('Pre-defined options')}
                  className={classes(
                    map.type === TargetFieldType.Preset && 'active'
                  )}
                  onClick={() => update(source.id, TargetFieldType.Preset)}
                >
                  <ListIcon />
                </TypeButton>
              )}

              <TypeButton
                title={translate('Twig code')}
                className={classes(
                  map.type === TargetFieldType.Custom && 'active'
                )}
                onClick={() => update(source.id, TargetFieldType.Custom)}
              >
                <CustomIcon />
              </TypeButton>

              <TypeButton
                title={translate('Freeform field')}
                className={classes(
                  map.type === TargetFieldType.Relation && 'active'
                )}
                onClick={() => update(source.id, TargetFieldType.Relation)}
              >
                <RelationIcon />
              </TypeButton>
            </TypeButtonGroup>

            <div>
              {map.type === TargetFieldType.Preset && (
                <Dropdown
                  value={map?.value}
                  showValues
                  emptyOption={translate('Select an option')}
                  onChange={(fieldUid) => {
                    update(source.id, TargetFieldType.Preset, fieldUid);
                  }}
                  options={source.options.map((opt) => ({
                    value: opt.key,
                    label: opt.label,
                  }))}
                />
              )}

              {map.type === TargetFieldType.Relation && (
                <FieldSelect
                  value={map?.value}
                  onChange={(fieldUid) => {
                    update(source.id, TargetFieldType.Relation, fieldUid);
                  }}
                />
              )}

              {map.type === TargetFieldType.Custom && (
                <TwigInput
                  type="text"
                  className="text fullwidth code"
                  placeholder="e.g. {{ yourField }} {{ otherField }}"
                  value={map.value}
                  onChange={(event) => {
                    update(
                      source.id,
                      TargetFieldType.Custom,
                      event.target.value
                    );
                  }}
                />
              )}
            </div>
          </MappingWrapper>
        );
      })}
    </MappingContainer>
  );
};
