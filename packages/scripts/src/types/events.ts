import type Freeform from '@components/front-end/plugin/freeform';

export type Callback = () => Promise<void | boolean>;

export type FreeformEvent = CustomEvent & {
  freeform: Freeform;
  form: HTMLFormElement;
  isBackButtonPressed?: boolean;
  addCallback: (callback: Callback, priority?: number) => void;
};

type AllTypes = string | number | boolean | null | undefined;
type GenericValue = AllTypes | AllTypes[] | Record<string, AllTypes | AllTypes[]>;

type Action = {
  name: string;
  metadata: Record<string, string>;
};

export type FreeformResponse = {
  success: boolean;
  finished: boolean;
  onSuccess: string;
  id: number;
  hash: string;
  values: Record<string, GenericValue>;
  errors: Record<string, string[]>;
  formErrors: string[];
  returnUrl?: string;
  submissionId?: number;
  submissionToken?: string;
  html: string;
  actions: string[];
  multipage: boolean;
  duplicate: boolean;
  freeform_payload: string;
};

export type FreeformResponseWithToken = FreeformResponse & { storageToken: string };

export type FreeformResponseEvent = FreeformEvent & {
  response: FreeformResponse;
};

export type FreeformActionsEvent = FreeformResponseEvent & {
  actions: Action[];
};

export type StorageResponse = {
  token: string;
};
