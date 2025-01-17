import kebabCase from 'lodash/kebabcase';

export const createId = (name?: string): string => {
  return kebabCase(name);
};
