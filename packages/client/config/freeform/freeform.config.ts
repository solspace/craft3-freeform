import type { Site } from '@ff-client/types/sites';

export enum Edition {
  Pro = 'pro',
  Lite = 'lite',
  Express = 'express',
}

type Config = {
  templates: {
    native: boolean;
  };
  feed: boolean;
  limits: {
    forms?: number;
    fields?: number;
  };
  metadata: {
    craft: {
      is5: boolean;
      version: string;
    };
    freeform: {
      version: string;
      canCreate: boolean;
      canDelete: boolean;
    };
  };
  editions: {
    edition: Edition;
    tiers: Edition[];

    is: (edition: Edition) => boolean;
    isAtLeast: (edition: Edition) => boolean;
    isAtMost: (edition: Edition) => boolean;
  };
  sites: {
    enabled: boolean;
    current: number;
    list: Site[];
  };
  limitations: {
    items: null | Record<string, boolean | string | string[]>;
    can: (key: string) => boolean | undefined;
    get: (key: string) => boolean | string | string[] | undefined;
  };
};

const element = document.getElementById('freeform-config') as HTMLScriptElement;
const baseConfig = JSON.parse(element?.innerHTML || '[]') as Config;

const config: Config = {
  ...baseConfig,
  editions: {
    ...baseConfig.editions,

    is: (edition) => config.editions.edition === edition,
    isAtLeast: (edition) => {
      const editions = config.editions.tiers;
      const index = editions.indexOf(edition);

      if (index === -1) {
        throw new Error(`Unknown edition: ${edition}`);
      }

      return editions.indexOf(config.editions.edition) >= index;
    },
    isAtMost: (edition) => {
      const editions = config.editions.tiers;
      const index = editions.indexOf(edition);

      if (index === -1) {
        throw new Error(`Unknown edition: ${edition}`);
      }

      return editions.indexOf(config.editions.edition) <= index;
    },
  },
  limitations: {
    ...baseConfig.limitations,

    can: (key: string): boolean | undefined => {
      const limitations = config.limitations?.items;
      if (!limitations) {
        return true;
      }

      const parts = key.split('.');

      for (let i = 0; i < parts.length; i++) {
        const currentChain = parts.slice(0, i + 1).join('.');

        if (limitations[currentChain] === false) {
          return false;
        }
      }

      return limitations[key] !== undefined ? Boolean(limitations[key]) : true;
    },

    get: (key: string): boolean | string | string[] | undefined => {
      const limitations = config.limitations?.items;
      if (!limitations) {
        return undefined;
      }

      return limitations[key];
    },
  },
};

export default config;
