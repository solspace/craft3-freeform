import { useMemo } from 'react';
import { useSelector } from 'react-redux';
import config from '@config/freeform/freeform.config';
import { pageSelecors } from '@editor/store/slices/layout/pages/pages.selectors';
import type {
  GenericValue,
  VisibilityFilter,
} from '@ff-client/types/properties';
import { filterTest } from '@ff-client/utils/filters';

export const useVisibility = (
  filters: VisibilityFilter[],
  values: GenericValue[]
): boolean => {
  const page = useSelector(pageSelecors.current);

  return useMemo(() => {
    if (filters.length === 0) {
      return true;
    }

    const context = { config, page };

    try {
      return filterTest(filters, values, context);
    } catch (error) {
      console.error(
        `Failed to evaluate visibility expression: ${filters.join(' && ')}`,
        error
      );

      return false;
    }
  }, [filters, values]);
};
