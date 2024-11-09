import type { GenericValue } from '@ff-client/types/properties';

export type TranslationType = 'fields' | 'form' | 'pages';

export type TranslationItems = Record<string, GenericValue>;
export type TranslationSet = Record<string, TranslationItems>;

export type TranslationState = {
  [siteId: string]: {
    [type in TranslationType]: {
      [namespace: string]: TranslationItems;
    };
  };
};

export type UpdateProps = {
  siteId: number;
  type: TranslationType;
  namespace: string;
  handle: string;
  value: string;
};

export type RemoveProps = {
  siteId: number;
  type: TranslationType;
  namespace: string;
  handle: string;
};
