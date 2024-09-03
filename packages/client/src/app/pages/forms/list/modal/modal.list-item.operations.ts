import type { Dispatch, SetStateAction } from 'react';
import { useCallback } from 'react';
import { useSiteContext } from '@ff-client/contexts/site/site.context';
import type {
  FormGroup,
  FormGroupsListRefs,
  FormWithGroup,
  GroupItem,
} from '@ff-client/types/form-groups';
import Sortable from 'sortablejs';
import { v4 } from 'uuid';

type StateSetter<T> = Dispatch<SetStateAction<T>>;

type GroupOperations = {
  addGroup: () => void;
  updateGroupInfo: (property: 'label', value: string, groupUid: string) => void;
  syncFormGroupsRefs: () => FormGroup;
};

export const useFormGroupsOperations = (
  initialState: FormWithGroup,
  setState: StateSetter<FormWithGroup>,
  formGroupsListRefs: React.MutableRefObject<FormGroupsListRefs>
): GroupOperations => {
  const { getCurrentHandleWithFallback, current } = useSiteContext();

  const addGroup = useCallback(() => {
    setState((prevState) => ({
      ...prevState,
      formGroups: {
        ...prevState.formGroups,
        site: prevState.formGroups?.site
          ? prevState.formGroups.site
          : getCurrentHandleWithFallback(),
        groups: [
          ...(prevState.formGroups?.groups || []),
          {
            uid: v4(),
            label: '',
            formIds: [],
          },
        ],
      },
    }));
  }, [setState, getCurrentHandleWithFallback]);

  const updateGroupInfo = useCallback(
    (property: 'label', value: string, groupUid: string) => {
      setState((prevState) => ({
        ...prevState,
        formGroups: {
          ...prevState.formGroups,
          groups: prevState.formGroups.groups.map((group) =>
            group.uid === groupUid ? { ...group, [property]: value } : group
          ),
        },
      }));
    },
    [setState]
  );

  const syncFormGroupsRefs = useCallback((): FormGroup => {
    const groupUIDs = Sortable.get(
      formGroupsListRefs.current.groupWrapper
    ).toArray();

    const sortedGroups = groupUIDs
      .map((groupUid) => {
        const existingGroup = initialState.formGroups?.groups.find(
          (group) => group.uid === groupUid
        );

        if (existingGroup) {
          const groupWithoutForms = { ...existingGroup };
          delete groupWithoutForms.forms;

          return {
            ...groupWithoutForms,
            formIds: Sortable.get(formGroupsListRefs.current[groupUid])
              .toArray()
              .map(Number),
          };
        }

        return null;
      })
      .filter(Boolean) as GroupItem[];

    return {
      siteId: initialState.formGroups?.siteId || current?.id,
      site: initialState.formGroups?.site || getCurrentHandleWithFallback(),
      groups: sortedGroups,
    };
  }, [formGroupsListRefs, initialState, getCurrentHandleWithFallback]);

  return {
    addGroup,
    updateGroupInfo,
    syncFormGroupsRefs,
  };
};
