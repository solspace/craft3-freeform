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

function requestFormTemplates() {
  return {
    type: ActionTypes.REQUEST_FORM_TEMPLATES,
  };
}

function receiveFormTemplates(templateData) {
  return {
    type: ActionTypes.RECEIVE_FORM_TEMPLATES,
    templateData,
  };
}

export function invalidateFormTemplates() {
  return {
    type: ActionTypes.INVALIDATE_FORM_TEMPLATES,
  };
}

export function fetchFormTemplatesIfNeeded(hash = null, autoselectId = null) {
  return function (dispatch, getState) {
    if (shouldFetchFormTemplates(getState())) {
      dispatch(requestFormTemplates());

      const url = urlBuilder('freeform/api/form-templates');
      return fetch(url, { credentials: 'same-origin' })
        .then((response) => response.json())
        .then((json) => {
          dispatch(receiveFormTemplates(json));
          if (hash && autoselectId) {
            dispatch(updateProperty(hash, { formTemplate: autoselectId }));

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

function shouldFetchFormTemplates(state) {
  const templates = state.templates.list;

  if (!templates) {
    return true;
  } else if (state.templates.isFetching) {
    return false;
  } else {
    return state.templates.didInvalidate;
  }
}
