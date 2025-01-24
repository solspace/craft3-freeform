/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import * as ActionTypes from '../constants/ActionTypes';

export const swapPage = (newIndex, oldIndex) => ({
  type: ActionTypes.SWAP_PAGE,
  newIndex,
  oldIndex,
  properties: 'pageswap',
});

export const placeholderPage = (pageIndex) => ({
  type: ActionTypes.ADD_PLACEHOLDER_PAGE,
  pageIndex,
});
