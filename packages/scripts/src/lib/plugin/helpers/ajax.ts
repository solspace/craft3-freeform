type Headers = Record<string, string | boolean | number>;

type Options = {
  headers?: Headers;
};

type ExtendedOptions = Options & {
  method?: string;
  data?: Document | BodyInit | null;
};

type ResponseObject<D> = {
  status: number;
  data: D;
};

type AjaxGET = <R>(url: URL | string, options?: Options) => Promise<ResponseObject<R>>;
type AjaxPOST = <R, D = object>(url: URL | string, data: D, options?: Options) => Promise<ResponseObject<R>>;

const get: AjaxGET = async (url, options = {}) =>
  new Promise((resolve, reject) => {
    const xhr = createXhrRequest(resolve, reject, options);
    xhr.open('GET', url);
    xhr.send();
  });

const post: AjaxPOST = async (url, data, options = {}) =>
  new Promise((resolve, reject) => {
    const xhr = createXhrRequest(resolve, reject, options);
    xhr.open('POST', url);
    xhr.send(JSON.stringify(data));
  });

type CreateXhrRequest = <T>(
  resolve: (value: T) => void,
  reject: (reason?: unknown) => void,
  options?: Options
) => XMLHttpRequest;

const createXhrRequest: CreateXhrRequest = (resolve, reject, options) => {
  const xhr = new XMLHttpRequest();

  xhr.setRequestHeader('Cache-Control', 'no-cache');
  xhr.setRequestHeader('Content-Type', 'application/json');

  attachHeaders(xhr, options?.headers);

  xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  xhr.setRequestHeader('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');

  xhr.onload = () => {
    console.log('success', xhr.response);
    resolve(xhr.response);
  };

  xhr.onerror = () => {
    reject(xhr.response);
  };

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
    const xhr = createXhrRequest(resolve, reject, options);
    xhr.open(options?.method || 'GET', url);
    xhr.send(JSON.stringify(options.data));
  });

ajax.get = get;
ajax.post = post;
