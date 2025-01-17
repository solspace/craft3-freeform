import { camelCase } from 'lodash';

import type { MiddlewareImplementation } from '../middleware';

type Args = {
  target: string;
  camelize?: boolean;
  bypassConditions?: Array<{
    name: string;
    isTrue: boolean;
  }>;
};

const injectInto: MiddlewareImplementation<string, Args> = (
  value,
  { target, camelize = false, bypassConditions },
  context,
  updateCallback
) => {
  if (bypassConditions !== undefined) {
    for (const condition of bypassConditions) {
      if (Boolean(context?.[condition.name]) === condition.isTrue) {
        return value;
      }
    }
  }

  let targetValue = value;

  if (camelize) {
    targetValue = camelCase(targetValue);
  }

  updateCallback?.(target, targetValue);

  return value;
};

export default injectInto;
