import { scrollBar } from '@ff-client/styles/mixins';
import { colors, spacings } from '@ff-client/styles/variables';
import styled from 'styled-components';

export const PropertyEditorWrapper = styled.div`
  flex: 1;

  display: flex;
  flex-direction: column;
  gap: ${spacings.xl};

  padding: ${spacings.xl};

  background: ${colors.white};
  overflow-y: auto;

  ${scrollBar};

  --background-color: ${colors.white};
  --margins: -24px;

  h1 {
    padding: 0;
    margin-top: -11px;
    margin-bottom: -5px;
  }
`;

export const SettingsWrapper = styled.div`
  display: flex;
  flex-direction: column;
  gap: ${spacings.md};
`;
