import type { SaveSubscriber } from '@editor/store/middleware/state-persist';
import { TOPIC_SAVE } from '@editor/store/middleware/state-persist';

const persistTranslations: SaveSubscriber = (_, data) => {
  const { state, persist } = data;

  persist.translations = state.translations;
};

PubSub.subscribe(TOPIC_SAVE, persistTranslations);
