import React, { useState } from 'react';
import { Dropdown } from '@components/elements/custom-dropdown/dropdown';
import { FormComponent } from '@components/form-controls';
import { Control } from '@components/form-controls/control';
import { ControlWrapper } from '@components/form-controls/control.styles';
import { Button } from '@components/form-controls/control-types/options/options.styles';
import { FlexColumn } from '@components/layout/blocks/flex';
import config from '@config/freeform/freeform.config';
import type { GenericValue } from '@ff-client/types/properties';
import { PropertyType } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';
import type { UseQueryResult } from '@tanstack/react-query';

import type {
  ConfigurableOptionsConfiguration,
  ConfigurationProps,
} from '../../options.types';
import type { OptionTypeProvider } from '../sources.types';

type Props = ConfigurationProps<ConfigurableOptionsConfiguration> & {
  typeProviderQuery: () => UseQueryResult<OptionTypeProvider[]>;
};

export const ConfigurableOptions: React.FC<Props> = ({
  value,
  updateValue,
  property,
  typeProviderQuery,
  convertToCustomValues,
}) => {
  const [typeClass, setTypeClass] = useState(value.typeClass);
  const { data, isFetching } = typeProviderQuery();

  const selectedTypeProvider = data?.find(
    (type) => type.typeClass === typeClass
  );

  return (
    <FlexColumn>
      {property.showEmptyOption && (
        <FormComponent
          property={{
            type: PropertyType.String,
            label: 'Empty Option Label (optional)',
            handle: 'emptyOption',
          }}
          context={value}
          value={value.emptyOption}
          updateValue={(currentValue) => {
            updateValue({
              ...value,
              emptyOption: currentValue as string,
            });
          }}
        />
      )}

      <Control
        property={{
          type: PropertyType.Select,
          label: 'Type',
          handle: 'predefinedOptionTypeClass',
          options: [],
        }}
      >
        <Dropdown
          emptyOption="Choose type"
          loading={isFetching}
          value={value.typeClass}
          onChange={(selectedValue) => {
            const properties: GenericValue = {};
            const provider = data?.find(
              (type) => type.typeClass === selectedValue
            );

            if (provider) {
              provider.properties.map((property) => {
                properties[property.handle] = property.value;
              });
            }

            setTypeClass(selectedValue);
            updateValue({
              ...value,
              typeClass: selectedValue,
              properties,
            });
          }}
          options={
            data &&
            data.map((typeProvider) => ({
              label: typeProvider.name,
              value: typeProvider.typeClass,
            }))
          }
        />
      </Control>

      {selectedTypeProvider &&
        selectedTypeProvider.properties.map((property) => {
          let currentPropertyValue = '';
          if (value?.properties?.[property.handle] !== undefined) {
            currentPropertyValue = value.properties[property.handle];
          } else if (property.value !== undefined) {
            currentPropertyValue = property.value as string;
          }

          return (
            <FormComponent
              key={property.handle}
              property={property}
              context={value}
              value={currentPropertyValue}
              updateValue={(selectedValue) => {
                updateValue({
                  ...value,
                  properties: {
                    ...value.properties,
                    [property.handle]: selectedValue,
                  },
                });
              }}
            />
          );
        })}
      {typeClass && config.limitations.can('layout.options.convert') && (
        <ControlWrapper className="spacing-small">
          <Button
            className="btn small"
            onClick={() => {
              if (
                !confirm(
                  translate(
                    'Are you sure? This will allow you to customize and reorder the options, but they will become out of sync with the Element or Predefined options currently configured.'
                  )
                )
              ) {
                return;
              }

              convertToCustomValues();
            }}
          >
            {translate('Convert to Custom Values')}
          </Button>
        </ControlWrapper>
      )}
    </FlexColumn>
  );
};
