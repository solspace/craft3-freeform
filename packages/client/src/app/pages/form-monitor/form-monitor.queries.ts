import type { UseQueryResult } from '@tanstack/react-query';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export interface FormTest {
  id: number;
  formId: number;
  dateAttempted: string;
  dateCompleted: string;
  status: 'success' | 'failed' | 'pending';
  response: string;
  responseCode: number;
  customerId: number;
  screenshot?: string;
}

export interface TestStats {
  success: number;
  failed: number;
  pending: number;
  total: number;
  percentage: {
    success: number;
    failed: number;
    pending: number;
  };
}

export interface FormWithStats {
  formId: number;
  formUrl: string;
  enabled?: boolean;
  stats: TestStats;
}

export interface PaginationData {
  total: number;
  totalPages: number;
  currentPage: number;
  limit: number;
  offset: number;
}

export interface FormTestsResponse {
  tests: FormTest[];
  pagination: PaginationData;
  stats: TestStats;
}

interface QueryParams {
  limit?: number;
  offset?: number;
  sort?: 'asc' | 'desc';
}

export const QKFormMonitor = {
  forms: ['form-monitor', 'forms'],
  formStats: ['form-monitor', 'stats'],
  formTests: (id?: number, params?: QueryParams) => [
    'form-monitor',
    'tests',
    id,
    params,
  ],
} as const;

export const useFMForms = (): UseQueryResult<number[]> => {
  return useQuery(QKFormMonitor.forms, () =>
    axios.get<number[]>('/api/form-monitor/forms').then((res) => res.data)
  );
};

export const useFMFormStats = (): UseQueryResult<FormWithStats[]> => {
  return useQuery(QKFormMonitor.formStats, () =>
    axios
      .get<FormWithStats[]>('/api/form-monitor/stats')
      .then((res) => res.data)
  );
};

export const useFMFormTestsQuery = (
  id: number,
  params: QueryParams = {}
): UseQueryResult<FormTestsResponse> => {
  const { limit = 100, offset = 0, sort = 'desc' } = params;
  const url = `/api/form-monitor/forms/${id}/tests`;

  return useQuery(
    QKFormMonitor.formTests(id, { limit, offset, sort }),
    () => {
      return axios
        .get<FormTestsResponse>(url, { params: { limit, offset, sort } })
        .then((res) => {
          return res.data;
        });
    },
    {
      keepPreviousData: true,
      staleTime: 0,
      refetchOnWindowFocus: false,
    }
  );
};
