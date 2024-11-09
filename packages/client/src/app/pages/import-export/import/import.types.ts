type Field = {
  uid: string;
  type: string;
  label: string;
  placeholder: string;
  required: boolean;
  options: string[];
  default_value: string;
  name: string;
  handle: string;
  position: number;
};

type Row = {
  uid: string;
  fields: Field[];
};

type Page = {
  uid: string;
  label: string;
  layout: {
    uid: string;
    rows: Row[];
  };
};

export type Form = {
  uid: string;
  name: string;
  handle: string;
  pages: Page[];
};

export type Submissions = {
  form: {
    uid: string;
    name: string;
  };
  count: number;
};

export type NotificationTemplate = {
  uid: number | string;
  id: number | string;
  name: string;
  description: string;
};

export type PdfTemplate = {
  uid: string;
  id: number;
  name: string;
  description: string;
  fileName: string;
  body: string;
};

export type FileTemplate = {
  filePath: string;
  fileName: string;
  name: string;
};

export type Integration = {
  uid: string;
  name: string;
  icon: string;
};

export type TemplateCollection = {
  pdf: PdfTemplate[];
  notification: NotificationTemplate[];
  formatting: FileTemplate[];
  success: FileTemplate[];
};

export type FormImportData = {
  forms: Form[];
  formSubmissions: Submissions[];
  templates: TemplateCollection;
  integrations: Integration[];
  settings: boolean;
};

export type ImportStrategy = 'replace' | 'skip';

export type StrategyCollection = {
  forms: ImportStrategy;
  templates: ImportStrategy;
};

export type TemplateValues = {
  pdf: string[];
  notification: Array<string | number>;
  formatting: string[];
  success: string[];
};

export type ImportOptions = {
  forms: string[];
  formSubmissions: string[];
  templates: TemplateValues;
  integrations: string[];
  strategy: StrategyCollection;
  settings: boolean;
  password?: string;
};
