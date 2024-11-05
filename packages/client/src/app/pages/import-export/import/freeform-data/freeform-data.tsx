import type { ChangeEventHandler } from 'react';
import React, { useRef, useState } from 'react';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { Instructions } from '@components/form-controls/control.styles';
import { FormErrorList } from '@components/form-controls/error-list';
import { Label } from '@components/form-controls/label.styles';
import { ContentContainer } from '@components/layout/blocks/content-container';
import { Field } from '@components/layout/blocks/field';
import { LoadingText } from '@components/loaders/loading-text/loading-text';
import translate from '@ff-client/utils/translations';
import { generateUrl } from '@ff-client/utils/urls';
import axios from 'axios';

import { Preview } from '../../common/preview/preview';
import { Progress } from '../../common/progress/progress';
import { useProgressEvent } from '../../common/progress/progress.hooks';
import { Strategy } from '../../common/strategy/strategy';
import type {
  FormImportData,
  ImportOptions,
  StrategyCollection,
} from '../import.types';

import { FileInput, FileWrapper } from './freeform-data.styles';
import type { AvailableOptionResponse } from './freeform-data.types';

export const ImportFreeformData: React.FC = () => {
  const [fileToken, setFileToken] = useState<string>();
  const [availableOptions, setAvailableOptions] = useState<FormImportData>();
  const [errors, setErrors] = useState<string[]>();
  const password = useRef<string>(undefined);

  const [options, setOptions] = useState<ImportOptions>({
    forms: [],
    formSubmissions: [],
    templates: {
      pdf: [],
      notification: [],
      formatting: [],
      success: [],
    },
    integrations: [],
    strategy: {
      forms: 'skip',
      templates: 'skip',
    },
    settings: false,
  });

  const progressEvent = useProgressEvent();

  const onChange: ChangeEventHandler<HTMLInputElement> = async (event) => {
    setErrors(undefined);
    setFileToken(undefined);
    const file = event.target.files?.[0];

    if (!file) {
      return;
    }

    const formData = new FormData();
    formData.append('file', file);

    if (password.current) {
      formData.append('password', password.current);
    }

    try {
      const { data } = await axios.post<AvailableOptionResponse>(
        '/api/import/file',
        formData,
        {
          headers: { 'Content-Type': 'multipart/form-data' },
        }
      );

      setAvailableOptions(data.options);
      setFileToken(data.token);
    } catch (error) {
      setErrors(error?.errors?.import?.file);

      if (error.status === 403) {
        password.current = undefined;
        const userPassword = prompt(translate('Enter password'));
        if (!userPassword) {
          return;
        }

        password.current = userPassword;
        onChange(event);
      }
    }
  };

  const onImport = async (): Promise<void> => {
    if (!fileToken) {
      return;
    }

    progressEvent.clearProgress();

    const { data } = await axios.post('/api/import/prepare', {
      exporter:
        '\\Solspace\\Freeform\\Bundles\\Backup\\Export\\FileExportReader',
      options: {
        ...options,
        fileToken,
      },
    });

    const url = generateUrl(`/api/import?token=${data.token}`);
    progressEvent.triggerProgress(url);
  };

  return (
    <ContentContainer>
      <Breadcrumb id="import" label="Import" url="import/forms" />
      <Breadcrumb id="import-forms" label="Freeform Data" url="import/forms" />

      <FileWrapper>
        <Label>{translate('Upload a Freeform Export zip file')}</Label>
        <FileInput type="file" onChange={onChange} accept=".zip" />
        <Instructions>
          {translate('Accepts `.zip` files. Only upload files that you trust.')}
        </Instructions>
        <FormErrorList errors={errors} />
      </FileWrapper>

      {availableOptions && (
        <>
          <Field
            label={translate('Select Data')}
            instructions={translate(
              'Please select the data you want to import.'
            )}
          >
            <Preview
              disabled={false}
              data={availableOptions}
              options={options}
              onUpdate={(opts) => setOptions({ ...options, ...opts })}
            />
          </Field>

          <Strategy
            data={availableOptions}
            strategy={options.strategy}
            disabled={false}
            onUpdate={(strategy: StrategyCollection) =>
              setOptions((prev) => ({
                ...prev,
                strategy,
              }))
            }
          />

          <Field>
            <button className="btn submit" type="button" onClick={onImport}>
              <LoadingText
                loadingText={translate('Processing...')}
                loading={false}
                spinner
              >
                {translate('Begin Import')}
              </LoadingText>
            </button>
          </Field>

          <Progress
            label={translate('Import')}
            finishLabel={translate('Import completed successfully!')}
            event={progressEvent}
          />
        </>
      )}
    </ContentContainer>
  );
};
