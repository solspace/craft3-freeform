import React, { memo, useMemo } from 'react';
import translate from '@ff-client/utils/translations';

import { Instructions } from './control.styles';

type Props = {
  instructions: string;
};

const FormInstructions: React.FC<Props> = memo(({ instructions }) => {
  if (!instructions) {
    return null;
  }

  const translatedInstructions = useMemo(
    () => translate(instructions),
    [instructions]
  );

  const compiledInstructions = useMemo(() => {
    const parts = translatedInstructions.split(/`([^`]+)`/g);

    return parts.map((part, index) => {
      // Odd indices contain the text inside backticks
      if (index % 2 !== 0) {
        return <code key={index}>{part}</code>;
      }
      return part;
    });
  }, [translatedInstructions]);

  return <Instructions>{compiledInstructions}</Instructions>;
});

FormInstructions.displayName = 'FormInstructions';

export default FormInstructions;
