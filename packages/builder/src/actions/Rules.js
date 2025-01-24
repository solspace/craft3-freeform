/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import * as ActionTypes from '../constants/ActionTypes';

export const CRITERIA_TARGET_FIELD = 'field';
export const CRITERIA_TARGET_GOTO = 'goto';

export const addPageBlock = (pageHash) => ({
  type: ActionTypes.ADD_PAGE_BLOCK,
  pageHash,
});

export const removePageBlock = (pageHash) => ({
  type: ActionTypes.REMOVE_PAGE_BLOCK,
  pageHash,
});

export const addFieldRule = (pageHash, hash) => ({
  type: ActionTypes.ADD_FIELD_RULE,
  pageHash,
  hash,
});

export const removeFieldRule = (pageHash, index) => ({
  type: ActionTypes.REMOVE_FIELD_RULE,
  pageHash,
  index,
});

export const addGotoRule = (pageHash, targetPageHash) => ({
  type: ActionTypes.ADD_GOTO_RULE,
  pageHash,
  targetPageHash,
});

export const removeGotoRule = (pageHash, index) => ({
  type: ActionTypes.REMOVE_GOTO_RULE,
  pageHash,
  index,
});

export const toggleShow = (pageHash, index) => ({
  type: ActionTypes.TOGGLE_FIELD_RULE_SHOW,
  pageHash,
  index,
});

export const toggleMatchAll = (pageHash, target, index) => ({
  type: ActionTypes.TOGGLE_RULE_MATCH_ALL,
  pageHash,
  target,
  index,
});

// Criteria specific actions

export const addCriteria = (pageHash, target, ruleIndex, hash) => ({
  type: ActionTypes.ADD_RULE_CRITERIA,
  pageHash,
  target,
  ruleIndex,
  hash,
});

export const removeCriteria = (pageHash, target, ruleIndex, index) => ({
  type: ActionTypes.REMOVE_RULE_CRITERIA,
  pageHash,
  target,
  ruleIndex,
  index,
});

export const updateCriteriaHash = (pageHash, target, ruleIndex, index, hash) => ({
  type: ActionTypes.UPDATE_RULE_CRITERIA_HASH,
  pageHash,
  target,
  ruleIndex,
  index,
  hash,
});

export const toggleCriteriaEquals = (pageHash, target, ruleIndex, index) => ({
  type: ActionTypes.TOGGLE_RULE_CRITERIA_EQUALS,
  pageHash,
  target,
  ruleIndex,
  index,
});

export const updateCriteriaValue = (pageHash, target, ruleIndex, index, value) => ({
  type: ActionTypes.UPDATE_RULE_CRITERIA_VALUE,
  pageHash,
  target,
  ruleIndex,
  index,
  value,
});
