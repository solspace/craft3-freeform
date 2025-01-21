import React, { useEffect, useRef, useState } from 'react';
import { useFieldType } from '@ff-client/queries/field-types';
import translate from '@ff-client/utils/translations';

import { useQuerySurveyPreferences } from '../../results.queries';
import type { Result } from '../../results.types';
import { Chart } from '../../results.types';

import type { ChartProps } from './charts/index.types';
import { Average } from './components/average';
import { SettingsBlock } from './block.settings';
import { Settings } from './block.settings.styles';
import {
  Bulletin,
  Extras,
  Heading,
  HiddenBlock,
  Label,
  Numbers,
  SubHeading,
  Wrapper,
} from './block.styles';
import * as charts from './charts';

type Props = Result & {
  bulletin: number;
  responses: number;
};

const excludedExportTypes = [Chart.Hidden, Chart.Text];

export const Block: React.FC<Props> = ({
  field,
  responses,
  breakdown,
  skipped,
  bulletin,
  average,
  max,
}) => {
  const fieldType = useFieldType(field.class);

  const [chartType, setChartType] = useState<Chart>(Chart.Horizontal);
  const [showSettings, setShowSettings] = useState(false);

  const { data: preferences } = useQuerySurveyPreferences();

  const ref = useRef<HTMLLIElement>();

  useEffect(() => {
    if (preferences) {
      let type = preferences.fieldSettings.find(
        (pref) => pref.id === field.id
      )?.chartType;

      if (type === undefined) {
        type = preferences.chartDefaults?.[field.class] || Chart.Horizontal;
      }

      setChartType(type);
    } else {
      setChartType(Chart.Horizontal);
    }
  }, [preferences]);

  useEffect(() => {
    if (excludedExportTypes.includes(chartType)) {
      return;
    }
  }, [ref, chartType]);

  if (!preferences) {
    return null;
  }

  const { permissions } = preferences;

  const ChartElement: React.FC<ChartProps> = charts[chartType];

  if (chartType === Chart.Hidden) {
    return (
      <HiddenBlock>
        {permissions.reports && (
          <SettingsBlock
            fieldId={field.id}
            selectedChartType={chartType}
            isShown={showSettings}
            toggle={() => setShowSettings(!showSettings)}
            changeType={(type) => setChartType(type)}
          />
        )}
        --{' '}
        <span
          dangerouslySetInnerHTML={{
            __html: translate('Question <b>{index}</b> Hidden', {
              index: bulletin,
            }),
          }}
        ></span>{' '}
        --
      </HiddenBlock>
    );
  }

  return (
    <Wrapper ref={ref}>
      <Bulletin>
        <span>{bulletin}</span>
      </Bulletin>
      <Label>
        <Heading>
          {fieldType && (
            <span dangerouslySetInnerHTML={{ __html: fieldType.icon }} />
          )}
          {field.label}
        </Heading>
        <SubHeading>
          {translate('{answered} answered, {skipped} skipped', {
            answered: responses - skipped,
            skipped,
          })}

          {field.multiChoice && <Extras>{translate('multiple choice')}</Extras>}
        </SubHeading>
        <Average average={average} max={max} />
      </Label>
      <Settings>
        {permissions.reports && (
          <SettingsBlock
            fieldId={field.id}
            selectedChartType={chartType}
            isShown={showSettings}
            toggle={() => setShowSettings(!showSettings)}
            changeType={(type) => setChartType(type)}
          />
        )}
      </Settings>
      <Numbers>
        <ChartElement breakdown={breakdown} />
      </Numbers>
    </Wrapper>
  );
};
