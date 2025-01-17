import React, { useEffect } from 'react';
import Skeleton from 'react-loading-skeleton';
import { useParams } from 'react-router-dom';
import { Control } from '@components/form-controls/control';
import type { ControlType } from '@components/form-controls/types';
import type { FieldMappingProperty } from '@ff-client/types/properties';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { cloneDeep } from 'lodash';

import RefreshIcon from './icons/refresh.svg';
import { FieldMappingController } from './mapping.controller';
import { RefreshButton } from './mapping.styles';
import type { SourceField } from './mapping.types';
import { extractParameter } from './mapping.utilities';

const FieldMapping: React.FC<ControlType<FieldMappingProperty>> = ({
  value = {},
  property,
  errors,
  updateValue,
  context,
}) => {
  const { formId } = useParams();

  const params: Record<string, string> = { formId };
  if (property.parameterFields) {
    Object.entries(property.parameterFields).forEach(([key, value]) => {
      params[value] = extractParameter(context, key);
    });
  }

  const { data, isFetching, refetch } = useQuery<SourceField[]>(
    ['field-mapping', property.source, params],
    async () => {
      const response = await axios
        .get<SourceField[]>(property.source, { params })
        .then((res) => res.data);

      return response;
    },
    { staleTime: Infinity, cacheTime: Infinity }
  );

  useEffect(() => {
    if (isFetching || data === undefined) {
      return;
    }

    const availableProperties = data.map((item) => String(item.id));

    const valueClone = cloneDeep(value);
    let modified = false;
    Object.keys(value).forEach((key) => {
      if (!availableProperties.includes(key)) {
        delete valueClone[key];
        modified = true;
      }
    });

    if (modified) {
      updateValue(valueClone);
    }
  }, [isFetching, data, value]);

  return (
    <Control property={property} errors={errors}>
      <RefreshButton
        className="btn"
        disabled={isFetching}
        onClick={() => {
          params['refresh'] = 'true';
          refetch();
          delete params['refresh'];
        }}
      >
        <RefreshIcon />
      </RefreshButton>
      {data && (
        <FieldMappingController
          sources={data}
          mapping={value}
          updateValue={updateValue}
        />
      )}
      {!data && isFetching && (
        <div>
          <Skeleton width="40%" />
          <Skeleton width="35%" />
          <Skeleton width="42%" />
        </div>
      )}
    </Control>
  );
};

export default FieldMapping;
