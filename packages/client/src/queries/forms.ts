import { useParams } from 'react-router-dom';
import { useAppDispatch } from '@editor/store';
import { formActions } from '@editor/store/slices/form';
import { useSiteContext } from '@ff-client/contexts/site/site.context';
import type {
  ExtendedFormType,
  FormSettingNamespace,
  FormWithStats,
} from '@ff-client/types/forms';
import type { UseQueryResult } from '@tanstack/react-query';
import { useQuery } from '@tanstack/react-query';
import type { AxiosError } from 'axios';
import axios from 'axios';

export const QKForms = {
  base: ['forms'] as const,
  all: (site: string) => [...QKForms.base, site] as const,
  single: (id: number) => [...QKForms.base, id] as const,
  settings: () => [...QKForms.base, 'settings'] as const,
  usage: (id: number, siteId: number) =>
    [...QKForms.base, id, 'usage', siteId] as const,
};

export const useQueryFormsWithStats = (): UseQueryResult<
  FormWithStats[],
  AxiosError
> => {
  const { current, getCurrentHandleWithFallback } = useSiteContext();

  return useQuery<FormWithStats[], AxiosError>(
    QKForms.all(getCurrentHandleWithFallback()),
    () =>
      axios
        .get<FormWithStats[]>('/api/forms', {
          params: { site: current?.handle },
        })
        .then((res) => res.data)
  );
};

export const useQuerySingleForm = (
  id?: number
): UseQueryResult<ExtendedFormType, AxiosError> => {
  return useQuery<ExtendedFormType, AxiosError>(
    QKForms.single(id),
    () =>
      axios.get<ExtendedFormType>(`/api/forms/${id}`).then((res) => res.data),
    {
      staleTime: Infinity,
      enabled: !!id,
    }
  );
};

export const useQueryFormSettings = (): UseQueryResult<
  FormSettingNamespace[],
  AxiosError
> => {
  const dispatch = useAppDispatch();

  return useQuery<FormSettingNamespace[], AxiosError>(
    QKForms.settings(),
    () =>
      axios
        .get<FormSettingNamespace[]>(`/api/forms/settings`)
        .then((res) => res.data)
        .then((res) => res.sort((a, b) => a.order - b.order))
        .then((res) => {
          dispatch(formActions.setInitialSettings(res));

          return res;
        }),
    { staleTime: Infinity, cacheTime: Infinity }
  );
};

type FormUsage = Array<{
  id: number;
  title: string;
  type: string;
  status: string;
  url: string;
}>;

export const useQueryFormUsage = (): UseQueryResult<FormUsage, AxiosError> => {
  const { formId } = useParams();
  const { current } = useSiteContext();

  return useQuery(QKForms.usage(Number(formId), current.id), {
    queryFn: () =>
      axios
        .get(`/api/forms/${formId}/elements?site=${current.id}`)
        .then((res) => res.data),
  });
};
