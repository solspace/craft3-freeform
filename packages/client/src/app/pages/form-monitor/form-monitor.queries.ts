import type { UseQueryResult } from '@tanstack/react-query';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export const QKFormMonitor = {
  forms: ['form-monitor', 'forms'],
  formTests: (id?: number) =>
    id !== undefined
      ? [...QKFormMonitor.forms, id, 'form-tests']
      : [...QKFormMonitor.forms, 'tests'],
} as const;

export const useFMForms = (): UseQueryResult<number[]> => {
  return useQuery(QKFormMonitor.forms, () =>
    axios.get<number[]>('/api/form-monitor/forms').then((res) => res.data)
  );
};

type FMTest = {
  id: string;
  formId: number;
  dateAttempted: string;
  dateCompleted: string;
  status: 'success' | 'fail';
  response: null | string;
  responseCode: number;
};

type FMTestsResponse = FMTest[];

export const useFMFormTestsQuery = (
  id?: number
): UseQueryResult<FMTestsResponse> => {
  const url = id
    ? `/api/form-monitor/forms/${id}/tests`
    : '/api/form-monitor/tests';

  return useQuery(QKFormMonitor.formTests(id), () =>
    axios.get<FMTestsResponse>(url).then((res) => res.data)
  );
};
