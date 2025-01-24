/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import 'core-js/stable';
import 'regenerator-runtime/runtime';
import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { applyMiddleware, compose, createStore } from 'redux';
import thunkMiddleware from 'redux-thunk';
import * as FieldTypes from './constants/FieldTypes';
import ComposerApp from './containers/ComposerApp';
import composerReducers from './reducers/index';
import { fitInCraft } from './helpers/Resizer';

const enhancer = compose(
  applyMiddleware(thunkMiddleware),
  window.__REDUX_DEVTOOLS_EXTENSION__ ? window.__REDUX_DEVTOOLS_EXTENSION__() : (f) => f
);

const specialFields = [
  {
    type: FieldTypes.SUBMIT,
    label: translate('Submit'),
    labelNext: 'Submit',
    labelPrev: 'Previous',
    disablePrev: false,
    position: 'left',
    visible: true,
  },
  {
    type: FieldTypes.HTML,
    label: 'HTML',
    value: '<div>Html content</div>',
  },
];

if (isPro) {
  specialFields.push(
    {
      type: FieldTypes.SAVE,
      label: translate('Save & Continue Later'),
      position: 'left',
    },
    {
      type: FieldTypes.RICH_TEXT,
      label: 'Rich Text',
      value: '',
    },
    {
      type: FieldTypes.CONFIRMATION,
      label: translate('Confirm'),
      handle: 'confirm',
      placeholder: '',
    },
    {
      type: FieldTypes.PASSWORD,
      label: translate('Password'),
      handle: 'password',
      placeholder: '',
    }
  );
}

if (isRecaptchaEnabled && !isInvisibleRecaptchaSetUp) {
  specialFields.push({
    type: FieldTypes.RECAPTCHA,
    label: isHCaptcha ? 'hCaptcha' : 'reCAPTCHA',
    singleton: true,
    isHCaptcha,
  });
}

if (isPaymentEnabled) {
  const paymentFieldIndex = fieldList.findIndex((item) => item.type == FieldTypes.CREDIT_CARD_DETAILS);
  const paymentField = fieldList[paymentFieldIndex];
  fieldList.splice(paymentFieldIndex, 1);

  if (paymentGatewayList.length > 0) {
    const CreditCardDetails = require('./components/PropertyEditor/CreditCardDetails').default;
    const CreditCardExpDate = require('./components/PropertyEditor/CreditCardExpDate').default;
    const CreditCardCvc = require('./components/PropertyEditor/CreditCardCvc').default;
    const CreditCardNumber = require('./components/PropertyEditor/CreditCardNumber').default;

    specialFields.push({
      id: paymentField.id,
      type: paymentField.type,
      handle: paymentField.handle,
      label: '',
      fieldLabel: 'Credit Card',
      children: {
        [CreditCardNumber.getClassName()]: {
          label: 'Credit Card Number',
          required: true,
        },
        [CreditCardExpDate.getClassName()]: {
          label: 'Expiry Date',
          required: true,
        },
        [CreditCardCvc.getClassName()]: {
          label: 'CVC/CVV',
          required: true,
        },
      },
      layout: CreditCardDetails.LAYOUT_3_ROWS,
      singleton: true,
    });
  }
}

let store = createStore(
  composerReducers,
  {
    csrfToken: {
      name: Craft.csrfTokenName ? Craft.csrfTokenName : 'csrfToken',
      value: Craft.csrfTokenValue ? Craft.csrfTokenValue : '',
    },
    formId: formId,
    fields: {
      isFetching: false,
      didInvalidate: false,
      fields: fieldList,
      types: fieldTypeList,
    },
    specialFields,
    mailingLists: {
      isFetching: false,
      didInvalidate: false,
      list: mailingList,
    },
    integrations: {
      isFetching: false,
      didInvalidate: false,
      list: crmIntegrations,
    },
    notifications: {
      isFetching: false,
      didInvalidate: true,
      list: notificationList,
    },
    paymentGateways: {
      isFetching: false,
      didInvalidate: false,
      list: paymentGatewayList,
    },
    templates: {
      isFetching: false,
      didInvalidate: false,
      solspaceTemplates: solspaceFormTemplates,
      list: formTemplateList,
    },
    sourceTargets,
    customFields,
    craftFields,
    sites: {
      currentSiteId,
      list: sites,
    },
    generatedOptionLists: {
      isFetching: false,
      didInvalidate: false,
      cache: generatedOptions,
    },
    formTypes: formTypes,
    formStatuses: formStatuses,
    assetSources: assetSources,
    fileKinds: fileKinds,
    successTemplates: successTemplatesList,
    metadata: metadata,
    ...composerState,
  },
  enhancer
);

const rootElement = document.getElementById('freeform-builder');
fitInCraft(rootElement);
export const notificator = (type, message) => Craft.cp.displayNotification(type, message);
export const urlBuilder = (url) => Craft.getCpUrl(url);

export function translate(string, params = {}, category = 'freeform') {
  return Craft.t(category, string, params);
}

ReactDOM.render(
  <Provider store={store}>
    <ComposerApp
      saveUrl={Craft.getCpUrl('freeform/forms/save')}
      formUrl={Craft.getCpUrl('freeform/forms/{id}')}
      createFieldUrl={Craft.getCpUrl('freeform/api/quick-create-field')}
      createNotificationUrl={Craft.getCpUrl('freeform/api/quick-create-notification')}
      createTemplateUrl={Craft.getCpUrl('freeform/settings/add-demo-template')}
      finishTutorialUrl={Craft.getCpUrl('freeform/api/finish-tutorial')}
      showTutorial={showTutorial}
      defaultTemplates={defaultTemplates}
      notificator={notificator}
      isPro={isPro}
      canManageFields={canManageFields}
      canManageNotifications={canManageNotifications}
      canManageSettings={canManageSettings}
      isDbEmailTemplateStorage={isDbEmailTemplateStorage}
      isRulesEnabled={isRulesEnabled}
      renderHtml={renderFormHtmlInCpViews}
      reservedKeywords={reservedKeywords}
      isInvisibleRecaptchaSetUp={isInvisibleRecaptchaSetUp}
      isCommerceEnabled={isCommerceEnabled}
      isCalendarEnabled={isCalendarEnabled}
      csrf={{
        name: Craft.csrfTokenName ? Craft.csrfTokenName : 'csrfToken',
        token: Craft.csrfTokenValue ? Craft.csrfTokenValue : '',
      }}
    />
  </Provider>,
  rootElement
);
