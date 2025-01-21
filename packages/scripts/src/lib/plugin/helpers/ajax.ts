type Headers = Record<string, string | boolean | number>;

type Options = {
  headers?: Headers;
  cancelToken?: CancelToken;
  onUploadProgress?: (progress: ProgressEvent) => void;
};

type ExtendedOptions = Options & {
  method?: string;
  data?: Document | XMLHttpRequestBodyInit | null;
};

type ResponseObject<D> = {
  status: number;
  data: D;
};

type AjaxGET = <R>(url: URL | string, options?: Options) => Promise<ResponseObject<R>>;
type AjaxPOST = <R, D = unknown>(url: URL | string, data: D, options?: Options) => Promise<ResponseObject<R>>;

const get: AjaxGET = async (url, options = {}) =>
  new Promise((resolve, reject) => {
    const xhr = createXhrRequest('GET', url, resolve, reject, options);
    xhr.open('GET', url);
    xhr.send();
  });

const post: AjaxPOST = async (url, data, options = {}) =>
  new Promise((resolve, reject) => {
    const xhr = createXhrRequest('POST', url, resolve, reject, options);

    if (data instanceof FormData) {
      xhr.send(data);
    } else {
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify(data));
    }
  });

type CreateXhrRequest = <T>(
  method: string,
  url: string | URL,
  resolve: (value: ResponseObject<T>) => void,
  reject: (reason?: unknown) => void,
  options?: Options
) => XMLHttpRequest;

const createXhrRequest: CreateXhrRequest = (method, url, resolve, reject, options) => {
  const xhr = new XMLHttpRequest();
  xhr.open(method, url);

  xhr.setRequestHeader('Cache-Control', 'no-cache');
  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.setRequestHeader('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');

  attachHeaders(xhr, options?.headers);

  xhr.onload = () => {
    let data = xhr.response;
    try {
      data = JSON.parse(xhr.response);
    } catch {
      // Do nothing
    }

    resolve({
      status: xhr.status,
      data,
    });
  };

  xhr.onerror = () => {
    reject(new Error('Network error'));
  };

  xhr.onabort = () => {
    reject(new Error('Request aborted'));
  };

  if (options.onUploadProgress) {
    xhr.upload.onprogress = (event) => {
      options.onUploadProgress(event);
    };
  }

  if (options.cancelToken) {
    options.cancelToken._setCancelFn(() => {
      xhr.abort();
    });
  }

  return xhr;
};

const attachHeaders = (xhr: XMLHttpRequest, headers?: Headers) => {
  if (!headers) {
    return;
  }

  Object.entries(headers).forEach(([key, value]) => {
    xhr.setRequestHeader(key, String(value));
  });
};

export const ajax = <R>(url: URL | string, options?: ExtendedOptions): Promise<ResponseObject<R>> =>
  new Promise((resolve, reject) => {
    const xhr = createXhrRequest(options?.method || 'GET', url, resolve, reject, options);

    const data = options?.data;
    if (data instanceof FormData) {
      xhr.send(data);
    } else {
      xhr.setRequestHeader('Content-Type', 'application/json');
      xhr.send(JSON.stringify(data));
    }
  });

ajax.get = get;
ajax.post = post;

export class CancelToken {
  private cancelFn: () => void;

  constructor() {}

  cancel() {
    if (this.cancelFn) {
      this.cancelFn();
      this.cancelFn = null;
    }
  }

  _setCancelFn(fn: () => void) {
    this.cancelFn = fn;
  }
}
