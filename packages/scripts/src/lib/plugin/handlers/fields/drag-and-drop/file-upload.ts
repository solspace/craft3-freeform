import type Freeform from '@components/front-end/plugin/freeform';
import events from '@lib/plugin/constants/event-types';
import { ajax, CancelToken } from '@lib/plugin/helpers/ajax';
import { dispatchCustomEvent } from '@lib/plugin/helpers/event-handling';
import { filesize } from 'filesize';

import { addFieldErrors } from './error-handling';
import { createInput, createPreviewContainer } from './preview';
import type { FieldError, FileMetadata } from './types';
import { ErrorTypes, isImage } from './types';

export const loadExistingUploads = (container: HTMLElement, freeform: Freeform): void => {
  const fileCount = parseInt(container.dataset.fileCount || '0');
  if (fileCount) {
    const previewZone = container.querySelector('[data-preview-zone]');
    const { freeformFileUpload: handle } = container.dataset;

    const formData = new FormData(freeform.form as HTMLFormElement);
    formData.delete('action');
    formData.append('handle', handle);

    const baseUrl = container.getAttribute('data-base-url');

    ajax
      .post<FileMetadata[]>(`${baseUrl}/files`, formData, {
        headers: {
          'Freeform-Preflight': true,
        },
      })
      .then(({ data }) => {
        data.forEach((file) => {
          const previewContainer = createPreviewContainer(file, freeform);

          if (isImage(file.extension)) {
            const thumbnail = previewContainer.querySelector<HTMLElement>('[data-thumbnail]');
            thumbnail.setAttribute('data-has-preview', '');
          }

          const deleteFormData = new FormData(freeform.form as HTMLFormElement);
          deleteFormData.delete('action');
          deleteFormData.append('handle', handle);
          deleteFormData.append('id', file.id);

          const removeButton = previewContainer.querySelector<HTMLElement>('[data-remove-button]');
          removeButton.addEventListener('click', () => {
            if (confirm('Are you sure?')) {
              ajax
                .post(`${baseUrl}/files/delete`, deleteFormData)
                .then(() => {
                  previewZone.removeChild(previewContainer);
                  dispatchChange(container);
                })
                .catch((error) => {
                  alert(error.message);
                });
            }
          });

          previewContainer.appendChild(createInput(handle, file));
          previewContainer.setAttribute('data-completed', '');
          previewZone.appendChild(previewContainer);
        });

        dispatchChange(container);
      })
      .catch(console.log);
  }
};

export const handleFileUpload = (
  file: File,
  handle: string,
  container: HTMLElement,
  previewZone: Element,
  freeform: Freeform
): Promise<void> => {
  const token = new CancelToken();
  const handleCancelRequest = () => {
    token.cancel();
  };

  const matches = file.name.match(/.(\w+)$/i);
  const name = file.name;
  const size = filesize(file.size, { round: 1 });
  const extension = matches !== null ? matches[1].toLowerCase() : 'n/a';

  const previewContainer = createPreviewContainer({ name, extension, size }, freeform);
  const thumbnail = previewContainer.querySelector<HTMLElement>('[data-thumbnail]');
  const removeButton = previewContainer.querySelector<HTMLElement>('[data-remove-button]');

  if (isImage(extension)) {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onloadend = () => {
      thumbnail.setAttribute('data-has-preview', '');
      thumbnail.style.backgroundImage = `url(${reader.result.toString()})`;
    };
  }

  previewZone.appendChild(previewContainer);
  removeButton.addEventListener('click', handleCancelRequest);
  dispatchChange(container);

  const formData = new FormData(freeform.form as HTMLFormElement);
  formData.delete('action');
  formData.append('handle', handle);
  formData.append(handle, file);

  const baseUrl = container.getAttribute('data-base-url');

  return ajax
    .post<FileMetadata>(`${baseUrl}/files/upload`, formData, {
      cancelToken: token,
      onUploadProgress: (progress: ProgressEvent) => {
        const { total, loaded } = progress;
        const percent = Math.ceil(loaded / (total / 100));

        dispatchCustomEvent(events.dragAndDrop.onUploadProgress, { total, loaded, percent }, container);

        previewContainer.style.setProperty('--progress', `${percent}%`);

        if (percent >= 98) {
          // Prevent files from being removed if they're uploaded already, but still being processed
          removeButton.removeEventListener('click', handleCancelRequest);
        }
      },
    })
    .then((response) => {
      const deleteFormData = new FormData(freeform.form as HTMLFormElement);
      deleteFormData.delete('action');
      deleteFormData.append('handle', handle);
      deleteFormData.append('id', response.data.id);

      removeButton.removeEventListener('click', handleCancelRequest);
      removeButton.addEventListener('click', () => {
        if (confirm('Are you sure?')) {
          ajax
            .post(`${baseUrl}/files/delete`, deleteFormData)
            .then(() => {
              previewZone.removeChild(previewContainer);
              dispatchChange(container);
            })
            .catch((error) => {
              alert(error.message);
            });
        }
      });

      previewContainer.appendChild(createInput(handle, response.data));
      previewContainer.setAttribute('data-completed', '');
    })
    .catch((error) => {
      if (error.message === 'Request aborted') {
        previewZone.removeChild(previewContainer);
        dispatchChange(container);
        return;
      }

      removeButton.removeEventListener('click', handleCancelRequest);
      removeButton.addEventListener('click', () => {
        previewZone.removeChild(previewContainer);
        dispatchChange(container);
      });

      if (error?.response?.data?.type === ErrorTypes.FieldError) {
        const { messages } = error?.response?.data as FieldError;

        addFieldErrors(container, previewContainer, messages, freeform);
      } else {
        console.warn(error);
      }
    });
};

const dispatchChange = (container: HTMLElement) => {
  dispatchCustomEvent(events.dragAndDrop.onChange, { container }, container);
};
