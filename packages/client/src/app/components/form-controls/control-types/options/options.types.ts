import type { GenericValue, Property } from '@ff-client/types/properties';

export enum Source {
  Custom = 'custom',
  Elements = 'elements',
  Predefined = 'predefined',
}

export const sourceLabels: { [key in Source]: string } = {
  custom: 'Custom Options',
  elements: 'Elements',
  predefined: 'Predefined Options',
};

export type Option = {
  label: string;
  value: string;
  checked: boolean;
};

export type ElementOptionType = {
  typeClass: string;
  label: string;
  properties: Property[];
};

type BaseOptions = {
  source: Source;
};

export type ConfigurableOptionsConfiguration = BaseOptions & {
  source: Source.Elements | Source.Predefined;
  typeClass: string;
  properties: GenericValue;
};

export type CustomOptionsConfiguration = BaseOptions & {
  source: Source.Custom;
  useCustomValues: boolean;
  options: Option[];
};

export type OptionsConfiguration =
  | CustomOptionsConfiguration
  | ConfigurableOptionsConfiguration;
