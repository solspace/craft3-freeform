/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import * as ActionTypes from '../constants/ActionTypes';
import * as FieldTypes from '../constants/FieldTypes';
import { hashFromTime } from '../helpers/Utilities';
import { manageConnections } from './Connections';
import { modifyGroupValues } from './PropertyEditor';
import { manageRules, removePageCallback } from './Rules';
import { manageMatrixEditor } from './MatrixEditor';

function layout(state = [[]], action) {
  let clonedState = null;

  switch (action.type) {
    case ActionTypes.ADD_FIELD_TO_NEW_ROW:
      clonedState = [...state];
      const newRow = {
        id: hashFromTime(),
        columns: [action.hash],
      };

      if (!clonedState[action.pageIndex]) {
        clonedState[action.pageIndex] = [];
      }

      clonedState[action.pageIndex].push(newRow);
      return clonedState;

    case ActionTypes.ADD_COLUMN_TO_ROW:
      clonedState = [...state];
      clonedState[action.pageIndex][action.rowIndex].columns.splice(action.columnIndex, 0, action.hash);

      return clonedState;

    case ActionTypes.ADD_COLUMN_TO_NEW_ROW:
      return addColumnToNewRow(state, action);

    case ActionTypes.ADD_PAGE:
      return [...state, []];

    case ActionTypes.REMOVE_PAGE:
      return removePageFromLayout(state, action);

    case ActionTypes.SWAP_PAGE:
      clonedState = [...state.slice(0, action.oldIndex), ...state.slice(action.oldIndex + 1)];

      clonedState.splice(action.newIndex, 0, state[action.oldIndex]);

      return clonedState;

    default:
      return state;
  }
}

function properties(state = {}, action) {
  switch (action.type) {
    case ActionTypes.ADD_FIELD_TO_NEW_ROW:
    case ActionTypes.ADD_COLUMN_TO_ROW:
    case ActionTypes.ADD_COLUMN_TO_NEW_ROW:
      return { ...state, [action.hash]: action.properties };

    case ActionTypes.ADD_PAGE:
      return {
        ...state,
        [`page${action.index}`]: action.properties,
      };

    case ActionTypes.SWAP_PAGE:
      const { oldIndex, newIndex } = action;
      const oldKey = `page${oldIndex}`;
      const newKey = `page${newIndex}`;

      const oldLabel = state[oldKey].label;
      const newLabel = state[newKey].label;

      let rules = { ...state.rules.list };

      let oldRules = null;
      if (rules[oldKey]) {
        oldRules = { ...rules[oldKey] };
        delete rules[oldKey];
      }

      let newRules = null;
      if (rules[newKey]) {
        newRules = { ...rules[newKey] };
        delete rules[newKey];
      }

      if (oldRules) rules[newKey] = oldRules;
      if (newRules) rules[oldKey] = newRules;

      if (Array.isArray(rules)) {
        rules = { ...rules };
      }

      return {
        ...state,
        [`page${action.oldIndex}`]: {
          type: 'page',
          label: newLabel,
        },
        [`page${action.newIndex}`]: {
          type: 'page',
          label: oldLabel,
        },
        rules: {
          ...state.rules,
          list: rules,
        },
      };

    case ActionTypes.UPDATE_PROPERTY:
      return {
        ...state,
        [action.hash]: {
          ...state[[action.hash]],
          ...action.keyValueObject,
        },
      };

    case ActionTypes.RESET_PROPERTIES:
      return {
        ...state,
        [action.hash]: action.defaultProperties,
      };

    case ActionTypes.REMOVE_PROPERTY:
      const clonedState = { ...state };
      const propData = clonedState[action.hash];

      // If this is an email field property
      // Clean out all MailingList fields which have it
      // Set as the email field to use
      if (propData.type === FieldTypes.EMAIL) {
        for (let key in clonedState) {
          const prop = clonedState[key];
          if (prop.type === FieldTypes.MAILING_LIST) {
            if (prop.emailFieldHash === action.hash) {
              clonedState[key].emailFieldHash = '';
            }
          }
        }
      }

      delete clonedState[action.hash];

      return clonedState;

    default:
      return state;
  }
}

function repositionColumn(state = [], action) {
  const clonedState = [...state];

  const { pageIndex, rowIndex, columnIndex, newRowIndex, newColumnIndex } = action;
  const column = clonedState[pageIndex][rowIndex].columns[columnIndex];

  clonedState[pageIndex][rowIndex].columns.splice(columnIndex, 1);
  clonedState[pageIndex][newRowIndex].columns.splice(newColumnIndex, 0, column);

  cleanRows(clonedState);

  return clonedState;
}

function addColumnToNewRow(state = [], action) {
  const clonedState = [...state];
  const { pageIndex, rowIndex, hash, prevPageIndex = null } = action;

  for (let pageRowIndex of clonedState[pageIndex].keys()) {
    const hashIndex = clonedState[pageIndex][pageRowIndex].columns.indexOf(hash);
    if (hashIndex !== -1) {
      clonedState[pageIndex][pageRowIndex].columns.splice(hashIndex, 1);
    }
  }

  if (prevPageIndex !== null) {
    for (let prevPageRowIndex of clonedState[prevPageIndex].keys()) {
      const hashIndex = clonedState[prevPageIndex][prevPageRowIndex].columns.indexOf(hash);
      if (hashIndex !== -1) {
        clonedState[prevPageIndex][prevPageRowIndex].columns.splice(hashIndex, 1);
      }
    }
  }

  const newRow = { id: hashFromTime(), columns: [hash] };

  if (rowIndex === -1) {
    clonedState[pageIndex].push(newRow);
  } else {
    clonedState[pageIndex].splice(rowIndex, 0, newRow);
  }

  cleanRows(clonedState);

  return clonedState;
}

function cleanRows(layout) {
  for (let pageIndex of layout.keys()) {
    for (let rowIndex of layout[pageIndex].keys()) {
      const row = layout[pageIndex][rowIndex];

      if (row.columns.length === 0) {
        layout[pageIndex].splice(rowIndex, 1);
      }
    }
  }
}

function removeColumn(state = [], action) {
  const clonedState = [...state];

  const { pageIndex, rowIndex, columnIndex } = action;

  clonedState[pageIndex][rowIndex].columns.splice(columnIndex, 1);

  if (clonedState[pageIndex][rowIndex].columns.length === 0) {
    clonedState[pageIndex].splice(rowIndex, 1);
  }

  return clonedState;
}

function removePage(state = [], action) {
  const index = action.index;

  const pageFieldHashes = [];
  state.layout[index].map((row) => {
    row.columns.map((hash) => pageFieldHashes.push(hash));
  });

  const layout = [...state.layout.slice(0, index), ...state.layout.slice(index + 1)];

  const properties = removePageCallback(state.properties, action);
  delete properties[`page${index}`];

  pageFieldHashes.map((hash) => {
    if (properties[hash]) {
      delete properties[hash];
    }
  });

  // If the deleted page isn't the last one,
  // We move all trailing pages back by 1 index in the properties
  for (const key in properties) {
    if (!properties.hasOwnProperty(key)) {
      continue;
    }

    const matches = key.match(/^page(\d+)$/);
    if (matches && matches[1]) {
      const pageIndex = matches[1];
      if (pageIndex > index) {
        properties[`page${pageIndex - 1}`] = properties[`page${pageIndex}`];
        delete properties[`page${pageIndex}`];
      }
    }
  }

  return {
    ...state,
    layout,
    properties,
  };
}

export function composer(state = [], action) {
  let idx = null;
  let clonedState = null;

  switch (action.type) {
    case ActionTypes.ADD_FIELD_TO_NEW_ROW:
    case ActionTypes.ADD_PAGE:
    case ActionTypes.ADD_COLUMN_TO_ROW:
    case ActionTypes.ADD_COLUMN_TO_NEW_ROW:
    case ActionTypes.SWAP_PAGE:
      clonedState = { ...state };
      clonedState.layout = layout(state.layout, action);
      if (action.properties) {
        clonedState.properties = properties(state.properties, action);
      }

      return clonedState;

    case ActionTypes.REMOVE_PAGE:
      return removePage(state, action);

    case ActionTypes.REPOSITION_COLUMN:
      idx = action.pageIndex;

      if (state.layout[idx]) {
        return { ...state, layout: repositionColumn(state.layout, action) };
      }

      return state;

    case ActionTypes.REMOVE_COLUMN:
      idx = action.pageIndex;

      if (state.layout[idx]) {
        return { ...state, layout: removeColumn(state.layout, action) };
      }

      return state;

    case ActionTypes.UPDATE_PROPERTY:
    case ActionTypes.REMOVE_PROPERTY:
    case ActionTypes.RESET_PROPERTIES:
      return { ...state, properties: properties(state.properties, action) };

    case ActionTypes.ADD_VALUE_SET:
    case ActionTypes.CLEAN_UP_VALUES:
    case ActionTypes.UPDATE_VALUE_SET:
    case ActionTypes.UPDATE_IS_CHECKED:
    case ActionTypes.INSERT_VALUE:
    case ActionTypes.REMOVE_VALUE:
    case ActionTypes.TOGGLE_CUSTOM_VALUES:
    case ActionTypes.REORDER_VALUE_SET:
    case ActionTypes.REMOVE_VALUE_SET:
      return { ...state, properties: modifyGroupValues(state.properties, action) };

    case ActionTypes.ADD_CONNECTION:
    case ActionTypes.REMOVE_CONNECTION:
    case ActionTypes.UPDATE_CONNECTION:
      return {
        ...state,
        properties: {
          ...state.properties,
          connections: manageConnections(state.properties.connections, action),
        },
      };

    case ActionTypes.ADD_PAGE_BLOCK:
    case ActionTypes.REMOVE_PAGE_BLOCK:
    case ActionTypes.ADD_FIELD_RULE:
    case ActionTypes.REMOVE_FIELD_RULE:
    case ActionTypes.ADD_GOTO_RULE:
    case ActionTypes.REMOVE_GOTO_RULE:
    case ActionTypes.TOGGLE_FIELD_RULE_SHOW:
    case ActionTypes.TOGGLE_RULE_MATCH_ALL:
    case ActionTypes.ADD_RULE_CRITERIA:
    case ActionTypes.REMOVE_RULE_CRITERIA:
    case ActionTypes.UPDATE_RULE_CRITERIA_HASH:
    case ActionTypes.TOGGLE_RULE_CRITERIA_EQUALS:
    case ActionTypes.UPDATE_RULE_CRITERIA_VALUE:
      return {
        ...state,
        properties: {
          ...state.properties,
          rules: manageRules(state.properties.rules, action),
        },
      };

    case ActionTypes.MATRIX_ADD_ROW:
    case ActionTypes.MATRIX_REMOVE_ROW:
    case ActionTypes.MATRIX_SWAP_ROW:
    case ActionTypes.MATRIX_UPDATE_COLUMN:
      const propClone = { ...state.properties };
      const { hash, attribute } = action;

      if (!propClone[hash]) {
        return state;
      }

      const target = { ...propClone[hash] };

      if (!target[attribute]) {
        target[attribute] = [];
      }

      return {
        ...state,
        properties: {
          ...state.properties,
          [hash]: {
            ...target,
            [attribute]: manageMatrixEditor(target[attribute], action),
          },
        },
      };

    default:
      return state;
  }
}

export function context(state = [], action) {
  switch (action.type) {
    case ActionTypes.SWITCH_PAGE:
      return { ...state, page: action.index, hash: `page${action.index}` };

    case ActionTypes.SWITCH_HASH:
      return { ...state, hash: action.hash };

    default:
      return state;
  }
}

export function formId(state = null, action) {
  switch (action.type) {
    case ActionTypes.UPDATE_FORM_ID:
      return parseInt(action.id);

    default:
      return state;
  }
}
