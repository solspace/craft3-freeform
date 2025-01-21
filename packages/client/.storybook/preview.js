import React from 'react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { queryClient } from '@config/react-query';
import { PortalProvider } from '@editor/builder/contexts/portal.context';
import { EscapeStackProvider } from '@ff-client/contexts/escape/escape.context';
import { QueryClientProvider } from '@tanstack/react-query';

import '!style-loader!css-loader!../../../vendor/craftcms/cms/src/web/assets/tailwindreset/dist/css/tailwind_reset.css';
import '!style-loader!css-loader!../../../vendor/craftcms/cms/src/web/assets/cp/dist/css/cp.css';
import '!style-loader!css-loader!./base.css';

/** @type { import('@storybook/react').Preview } */
const preview = {
  decorators: [
    (Story) => (
      <DndProvider backend={HTML5Backend}>
        <QueryClientProvider client={queryClient}>
          <EscapeStackProvider>
            <PortalProvider>
              <Story />
            </PortalProvider>
          </EscapeStackProvider>
        </QueryClientProvider>
      </DndProvider>
    ),
  ],
  parameters: {
    controls: {
      matchers: {
        color: /(background|color)$/i,
        date: /Date$/i,
      },
    },
  },
};

export default preview;
