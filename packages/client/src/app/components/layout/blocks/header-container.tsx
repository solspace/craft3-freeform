import type { HTMLAttributes, PropsWithChildren } from 'react';
import React from 'react';
import styled from 'styled-components';

type Props = PropsWithChildren<
  HTMLAttributes<HTMLDivElement> & {
    extra?: React.ReactNode;
  }
>;

const HeaderElement = styled.header`
  width: auto !important;
`;

export const HeaderContainer: React.FC<Props> = ({
  children,
  extra,
  ...props
}) => {
  if (!props.style) {
    props.style = { paddingLeft: 0, paddingRight: 0 };
  }

  return (
    <div id="header-container">
      <HeaderElement id="header" {...props}>
        <div id="page-title" className="flex">
          <h1 className="screen-title">{children}</h1>
        </div>
        {extra}
      </HeaderElement>
    </div>
  );
};
