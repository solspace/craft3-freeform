import type { ReactNode } from 'react';
import type { TooltipProps as Props } from 'react-tippy';

declare module 'react-tippy' {
  export interface TooltipProps extends Props {
    children: ReactNode;
  }
}
