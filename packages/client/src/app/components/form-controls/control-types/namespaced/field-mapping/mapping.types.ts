export type SourceField = {
  id: number | string;
  label: string;
  required: boolean;
  type: string;
  options?: Array<{
    key: string;
    label: string;
    description?: string;
  }>;
};
