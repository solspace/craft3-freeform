import type {
  FormTestsResponse,
  TestStats,
} from '@ff-client/types/form-monitor';
import type { UseQueryResult } from '@tanstack/react-query';
import { useQuery } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import axios from 'axios';

export const QKFormMonitor = {
  base: ['form-monitor'] as const,
  tests: (formId: number, params?: { limit?: number; offset?: number }) =>
    [...QKFormMonitor.base, 'tests', formId, params] as const,
  stats: (formId: number) => [...QKFormMonitor.base, 'stats', formId] as const,
};

export const useFMFormTestsQuery = (
  formId: number,
  params: { limit?: number; offset?: number } = {}
): UseQueryResult<FormTestsResponse, AxiosError> => {
  const { limit = 100, offset = 0 } = params;

  return useQuery(
    QKFormMonitor.tests(formId, { limit, offset }),
    () =>
      axios
        .get<FormTestsResponse>(`/api/form-monitor/forms/${formId}/tests`, {
          params: { limit, offset },
        })
        .then((res) => res.data),
    {
      keepPreviousData: true,
      staleTime: 0,
      refetchOnWindowFocus: false,
      enabled: !!formId,
    }
  );
};

export const useFMFormStatsQuery = (
  formId: number
): UseQueryResult<TestStats, AxiosError> => {
  return useQuery(
    QKFormMonitor.stats(formId),
    () =>
      axios
        .get<TestStats>(`/api/form-monitor/forms/${formId}/stats`)
        .then((res) => res.data),
    {
      enabled: !!formId,
    }
  );
};
