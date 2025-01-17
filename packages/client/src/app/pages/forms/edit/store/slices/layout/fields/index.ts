import type { FieldType } from '@ff-client/types/fields';
import { type PropertyValueCollection } from '@ff-client/types/fields';
import type { GenericValue } from '@ff-client/types/properties';
import type { PayloadAction } from '@reduxjs/toolkit';
import { createSlice } from '@reduxjs/toolkit';
import { camelCase } from 'lodash';

import './fields.persistence';

type FieldErrors = {
  [key: string]: string[];
};

export type Field = Pick<FieldType, 'typeClass'> & {
  id?: number;
  uid: string;
  properties: PropertyValueCollection;
  errors?: FieldErrors;
  rowUid?: string;
  order?: number;
};

export type FieldStore = Field[];

type EditType = {
  uid: string;
  handle: string;
  value: GenericValue;
};

type EditBatch = {
  uid: string;
  typeClass: string;
  properties: PropertyValueCollection;
};

type MoveToPayload = {
  uid: string;
  rowUid: string;
  position: number;
};

type ErrorPayload = {
  [key: string]: FieldErrors;
};

const initialState: FieldStore = [];

export const fieldsSlice = createSlice({
  name: 'layout/fields',
  initialState,
  reducers: {
    set: (state, action: PayloadAction<FieldStore>) => {
      state.splice(0, state.length, ...action.payload);
    },
    add: (
      state,
      action: PayloadAction<{
        uid: string;
        rowUid: string;
        fieldType: FieldType;
        order?: number;
      }>
    ) => {
      const { uid, rowUid, fieldType, order } = action.payload;
      const highestOrder = Math.max(
        -1,
        ...state
          .filter((field) => field.rowUid === action.payload.rowUid)
          .map((field) => field.order)
      );

      const properties: PropertyValueCollection = {};
      fieldType.properties.forEach(
        (prop) => (properties[prop.handle] = prop.value)
      );

      if (!properties.label) {
        const count = state.filter(
          (field) => field.typeClass === fieldType.typeClass
        ).length;

        let label = fieldType.name;
        if (count > 0) {
          label += ` ${count}`;
        }

        properties.label = label;
        properties.handle = camelCase(label);
      }

      state.push({
        uid,
        rowUid,
        typeClass: fieldType.typeClass,
        properties,
        order: order !== undefined ? order : highestOrder + 1,
      });

      // shift all other fields on the right by 1 order
      if (order !== undefined) {
        state
          .filter((field) => field.rowUid === rowUid)
          .filter((field) => field.uid !== uid)
          .forEach((field) => {
            if (field.order >= order) {
              field.order += 1;
            }
          });
      }
    },
    remove: (state, { payload: uid }: PayloadAction<string>) => {
      state.splice(
        state.findIndex((item) => item.uid === uid),
        1
      );
    },
    removeBatch: (state, { payload: uids }: PayloadAction<string[]>) => {
      uids.forEach((uid) => {
        state.splice(
          state.findIndex((item) => item.uid === uid),
          1
        );
      });
    },
    edit: (state, action: PayloadAction<EditType>) => {
      const { uid, handle, value } = action.payload;

      state.find((field) => field.uid === uid).properties[handle] = value;
    },
    batchEdit: (state, action: PayloadAction<EditBatch>) => {
      const { uid, typeClass, properties } = action.payload;

      const field = state.find((field) => field.uid === uid);
      field.typeClass = typeClass;
      field.properties = properties;
    },
    clearErrors: (state) => {
      for (const field of state) {
        field.errors = undefined;
      }
    },
    setErrors: (state, action: PayloadAction<ErrorPayload>) => {
      const { payload } = action;

      for (const field of state) {
        field.errors = payload?.[field.uid];
      }
    },
    moveTo: (state, action: PayloadAction<MoveToPayload>) => {
      const { uid, rowUid, position } = action.payload;
      const movedField = state.find((field) => field.uid === uid);

      const previosRowUid = movedField.rowUid;
      const previousPosition = movedField.order;
      const isSameRow = previosRowUid === rowUid;

      if (previousPosition === undefined) {
        return;
      }

      movedField.rowUid = rowUid;
      movedField.order = position;

      if (!isSameRow) {
        // Reset the order of fields in previous row
        state
          .filter((field) => field.rowUid === previosRowUid)
          .forEach((field) => {
            const isAfterMovedField = field.order >= previousPosition;
            field.order -= isAfterMovedField ? 1 : 0;
          });

        // update all new row orders after the new field
        state
          .filter((field) => field.rowUid === rowUid)
          .filter((field) => field.uid !== movedField.uid)
          .forEach((field) => {
            const isAfterMovedField = field.order >= movedField.order;
            field.order += isAfterMovedField ? 1 : 0;
          });
      }

      if (isSameRow) {
        // re-calculate orders for the current row
        state
          .filter((field) => field.rowUid === rowUid)
          .filter((field) => field.uid !== movedField.uid)
          .forEach((field) => {
            if (field.order > previousPosition && field.order <= position) {
              field.order -= 1;
            }

            if (field.order < previousPosition && field.order >= position) {
              field.order += 1;
            }
          });
      }
    },
  },
});

const { actions } = fieldsSlice;
export { actions as fieldActions };

export default fieldsSlice.reducer;
