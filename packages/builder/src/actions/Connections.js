/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import * as ActionTypes from '../constants/ActionTypes';

export const addConnection = () => ({
  type: ActionTypes.ADD_CONNECTION,
});

export const removeConnection = (index) => ({
  type: ActionTypes.REMOVE_CONNECTION,
  index,
});

export const updateConnection = (index, properties) => ({
  type: ActionTypes.UPDATE_CONNECTION,
  index,
  properties,
});
