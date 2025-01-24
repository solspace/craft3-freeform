/**
 * Freeform for Craft CMS
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 * @see           https://docs.solspace.com/craft/freeform
 * @license       https://docs.solspace.com/license-agreement
 */

import { findDOMNode } from 'react-dom';
import { COLUMN, ROW } from '../constants/DraggableTypes';

export const NEW_ROW_HANDLE_SIZE = 20;

/**
 * Pushes placeholder state depending on where the column is currently being dragged over
 *
 * @param props
 * @param monitor
 * @param component
 */
export function handleColumnDrag(props, monitor, component) {
  const item = monitor.getItem();

  const shouldShowRowPlaceholder = shouldCreateNewRow(props, monitor, component);
  const { rowIndex, index, type } = props.placeholders;

  const newRowIndex = props.index;

  if (shouldShowRowPlaceholder) {
    if (type === ROW && rowIndex === newRowIndex) {
      return;
    }

    props.addRowPlaceholder(newRowIndex, item.hash);
  } else {
    const newIndex = calculateNewColumnIndex(props, monitor, component);

    if (newIndex === null) {
      if (type) {
        props.clearPlaceholders();
      }

      return;
    }

    if (type === COLUMN && rowIndex === newRowIndex && index === newIndex) {
      return;
    }

    props.addColumnPlaceholder(newRowIndex, newIndex, item.hash);
  }
}

/**
 * Handler for dropping columns
 *
 * If a column is dropped on a row - the correct column index is calculated and it is added to state
 * If a column is dropped in between rows - it gets added to a new row
 *
 * @param props
 * @param monitor
 * @param component
 */
export function handleColumnDrop(props, monitor, component) {
  const item = monitor.getItem();
  const shouldShowRowPlaceholder = shouldCreateNewRow(props, monitor, component);

  const rowIndex = props.index;

  if (shouldShowRowPlaceholder) {
    props.columnToNewRow(rowIndex, item.hash, null, item.pageIndex);
  } else {
    const newIndex = calculateNewColumnIndex(props, monitor, component);

    if (newIndex === null) {
      return;
    }

    props.moveColumn(item.index, item.rowIndex, newIndex, rowIndex, item.pageIndex);
  }
}

/**
 * Handler for dropping fields
 *
 * If a field is dropped on a row - the correct column index is calculated and it is added to state
 * If a field is dropped in between rows - it gets added to a new row
 *
 * @param props
 * @param monitor
 * @param component
 */
export function handleFieldDrop(props, monitor, component) {
  const item = monitor.getItem();
  const shouldShowRowPlaceholder = shouldCreateNewRow(props, monitor, component);

  const rowIndex = props.index;

  if (shouldShowRowPlaceholder) {
    props.columnToNewRow(rowIndex, item.hash, item.properties);
  } else {
    const newIndex = calculateNewColumnIndex(props, monitor, component);

    if (newIndex === null) {
      return;
    }

    props.addColumn(rowIndex, newIndex, item.hash, item.properties);
  }
}

/**
 * Handles row dragging - reorders the rows live
 *
 * @param props
 * @param monitor
 * @param component
 */
export function handleOptionRowDrag(props, monitor, component) {
  const item = monitor.getItem();

  const dragIndex = item.index;
  const hoverIndex = props.index;

  if (dragIndex === hoverIndex) {
    return;
  }

  const hoverBoundingRect = findDOMNode(component).getBoundingClientRect();
  const boundingBox = hoverBoundingRect.bottom - hoverBoundingRect.top;
  const clientOffset = monitor.getClientOffset();
  const hoverClientY = clientOffset.y - hoverBoundingRect.top;

  if (dragIndex < hoverIndex && hoverClientY < 0) {
    return;
  }

  if (dragIndex > hoverIndex && hoverClientY > boundingBox) {
    return;
  }

  props.reorderValueSet(item.hash, dragIndex, hoverIndex);
  monitor.getItem().index = hoverIndex;
}

/**
 * Handles row dragging - reorders the rows live
 *
 * @param props
 * @param monitor
 * @param component
 */
export function handleMatrixRowDrag(props, monitor, component) {
  const item = monitor.getItem();

  const dragIndex = item.rowIndex;
  const hoverIndex = props.rowIndex;

  if (dragIndex === hoverIndex) {
    return;
  }

  const hoverBoundingRect = findDOMNode(component).getBoundingClientRect();
  const boundingBox = hoverBoundingRect.bottom - hoverBoundingRect.top;
  const clientOffset = monitor.getClientOffset();
  const hoverClientY = clientOffset.y - hoverBoundingRect.top;

  if (dragIndex < hoverIndex && hoverClientY < 0) {
    return;
  }

  if (dragIndex > hoverIndex && hoverClientY > boundingBox) {
    return;
  }

  props.swapRow(dragIndex, hoverIndex);
  monitor.getItem().rowIndex = hoverIndex;
}

/**
 * Calculates the new column index based on how many columns a row already has
 * and the current X position of the cursor
 *
 * @param props
 * @param monitor
 * @param component
 * @returns {number|null}
 */
function calculateNewColumnIndex(props, monitor, component) {
  const item = monitor.getItem();
  const columnCount = props.columns.length;
  const { rowIndex } = props.placeholders;

  if (columnCount >= 4 && props.index !== item.rowIndex) {
    return null;
  }

  const sameRow = rowIndex === item.rowIndex;

  const splitCount = columnCount + (sameRow ? 0 : 1);

  const hoverBoundingRect = findDOMNode(component).getBoundingClientRect();
  const boundingBox = hoverBoundingRect.right - hoverBoundingRect.left;

  const blockSplit = boundingBox / splitCount;

  const clientOffset = monitor.getClientOffset();
  const hoverClientX = clientOffset.x - hoverBoundingRect.left;

  let newIndex = Math.floor(hoverClientX / blockSplit);

  return newIndex;
}

/**
 * Checks the Y position of the cursor, and based on the NEW_ROW_HANDLE_SIZE offset
 * decides if the placeholder for creating a new row should be placed or not
 *
 * @param props
 * @param monitor
 * @param component
 * @returns {boolean|null}
 */
function shouldCreateNewRow(props, monitor, component) {
  const item = monitor.getItem();

  if (item.columnCountInRow === 1 && (item.rowIndex === props.index || item.rowIndex + 1 === props.index)) {
    return null;
  }

  const hoverBoundingRect = findDOMNode(component).getBoundingClientRect();
  const clientOffset = monitor.getClientOffset();

  return hoverBoundingRect.top + NEW_ROW_HANDLE_SIZE > clientOffset.y;
}
