import type { ColumnDescription } from '../table.types';

export type TableEditorProps = {
  column: ColumnDescription;
  onUpdate: (column: ColumnDescription) => void;
};
