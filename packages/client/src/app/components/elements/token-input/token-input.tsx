import React, { useRef } from 'react';
import type Tagify from '@yaireo/tagify';
import Tags from '@yaireo/tagify/dist/react.tagify.jsx';

import { TokenInputWrapper } from './token-input.styles';

import '@yaireo/tagify/dist/tagify.css';

type Option = {
  value: string;
  name?: string;
  editable?: boolean;
};

type Props = {
  value: string;
  options?: Option[];
  onChange?: (value: string[]) => void;
  allowCustom?: boolean;
  placeholder?: string;
};

export const TokenInput: React.FC<Props> = ({
  value,
  options = [],
  onChange,
  allowCustom,
  placeholder,
}) => {
  const tagifyRef = useRef<Tagify>(null);

  return (
    <TokenInputWrapper>
      <Tags
        tagifyRef={tagifyRef}
        placeholder={placeholder}
        settings={{
          tagTextProp: 'name',
          enforceWhitelist: allowCustom ? false : true,
          whitelist: options,
          dropdown: {
            mapValueTo: 'name',
            enabled: 0,
          },
        }}
        value={value}
        onChange={(event) =>
          onChange(event.detail.tagify.getCleanValue().map((tag) => tag.value))
        }
      />
    </TokenInputWrapper>
  );
};
