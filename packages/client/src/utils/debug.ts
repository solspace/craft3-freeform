import type { GenericValue } from '@ff-client/types/properties';

const urlParams = new URLSearchParams(window.location.search);
const debugMode = process.env.DEBUG_MODE || urlParams.get('mode') === 'debug';

const colors = {
  blue: 'color: #068FFE',
  reset: '',
} as const;

type DebugConsole = Console & {
  dbg: (...messages: unknown[]) => void;
  colors: typeof colors;
};

export const debug: DebugConsole = new Proxy(console as DebugConsole, {
  get: (target: DebugConsole, prop: keyof DebugConsole) => {
    if (prop === 'colors') {
      return colors;
    }

    if (prop === 'dbg') {
      if (!debugMode) {
        return () => {};
      }

      return (...messages: GenericValue[]) => {
        target.log('🀄️🔆🔆🔆🀄️', ...messages);
      };
    }

    if (typeof target[prop] === 'function' && !debugMode) {
      return () => {};
    }

    return target[prop];
  },
});

const timers: Record<string, number> = {};
type TimeLogger = (id: string) => {
  start: number;
  tick: () => string;
};

export const time: TimeLogger = (id) => {
  let timer = timers[id];
  if (!timer) {
    timers[id] = performance.now();
    timer = timers[id];
  }

  return {
    start: timer,
    tick: (): string => ((performance.now() - timer) / 1000).toFixed(2),
  };
};
