/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import fetch from 'isomorphic-fetch';
import { notificator, urlBuilder } from '../app';
import * as ActionTypes from '../constants/ActionTypes';
import qwest from 'qwest';

const requestPaymentGateways = () => ({
  type: ActionTypes.REQUEST_PAYMENT_GATEWAYS,
});

const receivePaymentGateways = (crmData) => ({
  type: ActionTypes.RECEIVE_PAYMENT_GATEWAYS,
  crmData,
});

export const invalidatePaymentGateways = () => ({
  type: ActionTypes.INVALIDATE_PAYMENT_GATEWAYS,
});

const endRequestPaymentGateways = () => ({
  type: ActionTypes.END_REQUEST_PAYMENT_GATEWAYS,
});

const validateResponseAndReport = (json) => {
  if (json.errors || json.error) {
    let error = '';
    if (json.errors) {
      error = json.errors.join(', ');
    } else {
      error = json.error;
    }
    notificator('error', error);

    return false;
  }
  return true;
};

export function fetchPaymentGatewaysIfNeeded() {
  return function (dispatch, getState) {
    if (shouldFetchPaymentGateways(getState())) {
      dispatch(requestPaymentGateways());

      const url = urlBuilder('freeform/api/payment-gateways');
      return fetch(url, { credentials: 'same-origin' })
        .then((response) => response.json())
        .then((json) => {
          if (validateResponseAndReport(json)) {
            dispatch(receivePaymentGateways(json));
          } else {
            dispatch(endRequestPaymentGateways());
          }
        });
    } else {
      dispatch(endRequestPaymentGateways());
      Promise.resolve();
    }
  };
}

const endCreatePaymentPlan = () => ({
  type: ActionTypes.END_REQUEST_PAYMENT_GATEWAYS,
});

export function createPaymentPlan(plan) {
  return function (dispatch, getState) {
    const url = urlBuilder('freeform/api/payment-plans');
    const { csrfToken } = getState();
    const body = { [csrfToken.name]: csrfToken.value, ...plan };
    const params = { responseType: 'json' };

    return qwest
      .post(url, body, params)
      .then((response) => response.response)
      .then((json) => {
        if (validateResponseAndReport(json)) {
          dispatch(invalidatePaymentGateways());
          dispatch(fetchPaymentGatewaysIfNeeded());
        }
        dispatch(endCreatePaymentPlan());

        return json;
      })
      .catch((xhr, response) => !validateResponseAndReport(response.response) && dispatch(endCreatePaymentPlan()));
  };
}

const shouldFetchPaymentGateways = (state) => {
  const integrations = state.paymentGateways.list;

  if (!integrations) {
    return true;
  } else if (state.paymentGateways.isFetching) {
    return false;
  } else {
    return state.paymentGateways.didInvalidate;
  }
};
