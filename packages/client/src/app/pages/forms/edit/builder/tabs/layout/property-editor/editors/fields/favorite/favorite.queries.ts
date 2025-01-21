import type { Field } from '@editor/store/slices/layout/fields';
import { QKFavorites } from '@ff-client/queries/field-favorites';
import type { APIError } from '@ff-client/types/api';
import type {
  FieldFavorite,
  FieldType,
  PropertyValueCollection,
} from '@ff-client/types/fields';
import type {
  UseMutationOptions,
  UseMutationResult,
} from '@tanstack/react-query';
import { useQueryClient } from '@tanstack/react-query';
import { useMutation } from '@tanstack/react-query';
import type { AxiosResponse } from 'axios';
import axios from 'axios';

type Variables = {
  label: string;
  field: Field;
  type: FieldType;
};

type Payload = {
  label: string;
  typeClass: string;
  properties: PropertyValueCollection;
};

type FavoritesMutation = (variables: Variables) => Promise<AxiosResponse>;

const favoritesMutation: FavoritesMutation = ({ label, field, type }) => {
  const payload: Payload = {
    label,
    properties: field.properties,
    typeClass: type.typeClass,
  };

  return axios.post('/api/fields/favorites', payload);
};

export type FavoriteMutationResult = UseMutationResult<
  AxiosResponse<FieldType>,
  APIError,
  Variables
>;

export const useFavoritesMutation = (): FavoriteMutationResult => {
  const queryClient = useQueryClient();

  return useMutation<AxiosResponse, APIError, Variables, unknown>(
    favoritesMutation,
    {
      onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: QKFavorites.all });
      },
    }
  );
};

type FavoritesPayload = Record<number, PropertyValueCollection>;
type MutationOptions = Partial<
  UseMutationOptions<
    AxiosResponse<FavoritesPayload>,
    APIError,
    FavoritesPayload
  >
>;

export const useFavoritesUpdateMutation = (
  options: MutationOptions = {}
): UseMutationResult<
  AxiosResponse<FavoritesPayload>,
  APIError,
  FavoritesPayload
> => {
  const queryClient = useQueryClient();

  const originalOnSuccess = options?.onSuccess;
  options.onSuccess = (
    data: AxiosResponse<FavoritesPayload>,
    variables: FavoritesPayload,
    context: unknown
  ) => {
    originalOnSuccess?.(data, variables, context);
    queryClient.invalidateQueries(QKFavorites.all);
  };

  return useMutation((data: FavoritesPayload) => {
    return axios.post<FavoritesPayload>('/api/fields/favorites/update', data);
  }, options);
};

type DeleteMutationOptions = Partial<
  UseMutationOptions<AxiosResponse<number>, APIError, number>
>;

export const useFavoritesDeleteMutation = (
  options: DeleteMutationOptions = {}
): UseMutationResult<AxiosResponse<number>, APIError, number> => {
  const queryClient = useQueryClient();

  const originalOnSuccess = options?.onSuccess;
  options.onSuccess = (
    data: AxiosResponse<number>,
    variables: number,
    context: unknown
  ) => {
    originalOnSuccess?.(data, variables, context);

    const favoriteId = variables;
    queryClient.setQueryData(QKFavorites.all, (oldData: FieldFavorite[]) =>
      oldData.filter((favorite: FieldFavorite) => favorite.id !== favoriteId)
    );
  };

  return useMutation((data: number) => {
    return axios.delete(`/api/fields/favorites/${data}`);
  }, options);
};
