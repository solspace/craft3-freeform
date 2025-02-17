import React from 'react';
import config from '@config/freeform/freeform.config';
import type { FormWithStats } from '@ff-client/types/forms';
import translate from '@ff-client/utils/translations';

import { useCreateFormModal } from '../../modals/hooks/use-create-form-modal';

import { Card } from './card/card';
import { chartDataset } from './grid.empty.datasets';
import { MutedWrapper } from './grid.empty.styles';

const color = '#e0e0e0';
const generateFormData = (
  name: string,
  description: string,
  chartData: Array<{ uv: number }>,
  submissions: number,
  spam: number
): FormWithStats => ({
  uid: '',
  type: '',
  name,
  handle: '',
  isNew: true,
  chartData,
  links: [],
  counters: {
    submissions,
    spam,
  },
  formMonitor: {
    enabled: false,
  },
  settings: {
    general: {
      namespaceType: 'settings',
      namespace: 'general',
      description,
      color,
    },
  },
  dateArchived: null,
});

export const GridEmpty: React.FC = () => {
  const openCreateFormModal = useCreateFormModal();
  const { canCreate } = config.metadata.freeform;

  return (
    <div>
      {canCreate && (
        <>
          <p>
            {translate(
              `You don't have any forms yet. Create your first form now...`
            )}
          </p>

          <button className="btn submit add icon" onClick={openCreateFormModal}>
            {translate('New Form')}
          </button>
        </>
      )}

      {!canCreate && <p>{translate(`You don't have any forms.`)}</p>}

      <MutedWrapper>
        <Card
          form={generateFormData(
            'Contact Form',
            'Main contact form.',
            chartDataset[0],
            14,
            5
          )}
        />
        <Card
          form={generateFormData(
            'Customer Survey',
            'Customer satisfaction survey.',
            chartDataset[1],
            72,
            18
          )}
        />
        <Card
          form={generateFormData(
            'Newsletter',
            'Newsletter signup form.',
            chartDataset[2],
            138,
            7
          )}
        />
      </MutedWrapper>
    </div>
  );
};
