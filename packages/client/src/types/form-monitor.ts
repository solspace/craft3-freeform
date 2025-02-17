export interface FormTest {
  id: number;
  formId: number;
  dateAttempted: string;
  dateCompleted: string;
  status: 'success' | 'failed' | 'pending';
  response: string;
  responseCode: number;
  customerId: number;
  screenshot?: string;
}

export interface TestStats {
  success: number;
  failed: number;
  pending: number;
  total: number;
  percentage: {
    success: number;
    failed: number;
    pending: number;
  };
}

export interface FormTestsResponse {
  tests: FormTest[];
  pagination: {
    total: number;
    totalPages: number;
    currentPage: number;
    limit: number;
    offset: number;
  };
  stats: TestStats;
}
