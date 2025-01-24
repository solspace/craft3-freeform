/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import fetch from 'isomorphic-fetch';
import { switchHash, updateProperty } from '../actions/Actions';
import { urlBuilder } from '../app';
import * as ActionTypes from '../constants/ActionTypes';

function requestNotifications() {
  return {
    type: ActionTypes.REQUEST_NOTIFICATIONS,
  };
}

function receiveNotifications(notificationData) {
  return {
    type: ActionTypes.RECEIVE_NOTIFICATIONS,
    notificationData,
  };
}

export function invalidateNotifications() {
  return {
    type: ActionTypes.INVALIDATE_NOTIFICATIONS,
  };
}

export function fetchNotificationsIfNeeded(hash = null, autoselectId = null) {
  return function (dispatch, getState) {
    if (shouldFetchNotifications(getState())) {
      dispatch(requestNotifications());

      const url = urlBuilder('freeform/api/notifications');
      return fetch(url, { credentials: 'same-origin' })
        .then((response) => response.json())
        .then((json) => {
          dispatch(receiveNotifications(json));
          if (hash && autoselectId) {
            dispatch(updateProperty(hash, { notificationId: autoselectId }));

            // For some reason, the property update alone isn't enough
            // for React to refresh the select box, so I have to do a quick back-and-forth
            // with context hash
            dispatch(switchHash(''));
            dispatch(switchHash(hash));
          }
        });
    } else {
      Promise.resolve();
    }
  };
}

function shouldFetchNotifications(state) {
  const notifications = state.notifications.list;

  if (!notifications) {
    return true;
  } else if (state.notifications.isFetching) {
    return false;
  } else {
    return state.notifications.didInvalidate;
  }
}
