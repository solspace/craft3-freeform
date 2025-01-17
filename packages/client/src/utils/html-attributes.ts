import { kebabCase } from 'lodash';

export const createId = (name?: string): string => {
  return kebabCase(name);
};
