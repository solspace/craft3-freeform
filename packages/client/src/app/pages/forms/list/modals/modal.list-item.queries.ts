import { useSiteContext } from '@ff-client/contexts/site/site.context';
import { QKGroups } from '@ff-client/queries/form-groups';
import type { APIError } from '@ff-client/types/api';
import type { UpdateFormGroup } from '@ff-client/types/form-groups';
import type {
  UseMutationOptions,
  UseMutationResult,
} from '@tanstack/react-query';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

type FormGroupsMutationResult = UseMutationResult<
  unknown,
  APIError,
  UpdateFormGroup
>;

export const useFormGroupsMutation = (
  options: UseMutationOptions<unknown, APIError, UpdateFormGroup> = {}
): FormGroupsMutationResult => {
  const queryClient = useQueryClient();
  const { getCurrentHandleWithFallback } = useSiteContext();

  const originalOnSuccess = options?.onSuccess;
  options.onSuccess = (
    data: unknown,
    variables: UpdateFormGroup,
    context: unknown
  ) => {
    originalOnSuccess?.(data, variables, context);
    queryClient.invalidateQueries(QKGroups.all(getCurrentHandleWithFallback()));
  };

  return useMutation<unknown, APIError, UpdateFormGroup>(
    async (data: UpdateFormGroup) => {
      const { orderedFormIds, ...formGroupsData } = data;

      await axios.post('/api/forms/groups', formGroupsData);

      if (orderedFormIds && orderedFormIds.length > 0) {
        await axios.post('/api/forms/sort', {
          orderedFormIds: orderedFormIds,
        });
      }
    },
    options
  );
};
