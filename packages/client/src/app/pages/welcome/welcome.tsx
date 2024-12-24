import React from 'react';
import { NavLink } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import translate from '@ff-client/utils/translations';
import { generateUrl } from '@ff-client/utils/urls';

import CheckIcon from './check.icon.svg';
import { Logo } from './logo';
import { useWelcomeAnimations } from './welcome.animations';
import {
  Button,
  ButtonsWrapper,
  ExtraContentWrapper,
  InstallIcon,
  InstallText,
  InstallWrapper,
  LogoWrapper,
  WelcomeWrapper,
} from './welcome.styles';

export const Welcome: React.FC = () => {
  const { installed, extra, buttons } = useWelcomeAnimations();

  return (
    <WelcomeWrapper>
      <Breadcrumb id="welcome" label="Welcome" url="/forms" />
      <LogoWrapper>
        <Logo />
      </LogoWrapper>

      <InstallWrapper>
        <InstallIcon style={installed.icon}>
          <CheckIcon />
        </InstallIcon>
        <InstallText style={installed.text}>
          <span>{translate('Awesome! Freeform is successfully installed!')}</span>
        </InstallText>
      </InstallWrapper>

      <ExtraContentWrapper style={extra}>
        {translate(
          "Thanks for choosing Freeform! Craft will automatically set you up with the free Express edition. If you're excited to explore even more features, consider switching to the Lite or Pro edition! We've included some helpful links below to get you started. Enjoy!"
        )}
      </ExtraContentWrapper>

      <ButtonsWrapper>
        <Button style={buttons[0]} className="btn">
          <NavLink to="/forms">{translate('Create Forms')}</NavLink>
        </Button>
        <Button style={buttons[2]} className="btn">
          <a href={generateUrl('/settings/demo-templates')}>
            {translate('Install Demo')}
          </a>
        </Button>
        <Button style={buttons[1]} className="btn">
          <a href="https://docs.solspace.com/craft/freeform/v5/guides/getting-started/">
            {translate('Getting Started')}
          </a>
        </Button>
        <Button style={buttons[1]} className="btn submit">
          <a href={generateUrl('/settings')}>
            {translate('Configure Freeform')}
          </a>
        </Button>
      </ButtonsWrapper>
    </WelcomeWrapper>
  );
};
