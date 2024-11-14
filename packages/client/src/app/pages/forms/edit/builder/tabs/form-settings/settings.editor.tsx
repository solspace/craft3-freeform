import React from 'react';
import { useParams, useResolvedPath } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { useQueryFormSettings } from '@ff-client/queries/forms';
import type { FormSettingNamespace } from '@ff-client/types/forms';
import type { Section } from '@ff-client/types/properties';
import translate from '@ff-client/utils/translations';

import { FieldComponent } from './field-component';
import { TAB_USAGE } from './settings';
import {
  FormSettingsContainer,
  SectionContainer,
  SectionHeader,
} from './settings.editor.styles';
import { FormUsage } from './settings.usage';

export const SettingsEditor: React.FC = () => {
  const { sectionHandle } = useParams();
  const currentPath = useResolvedPath('');

  const { data } = useQueryFormSettings();
  if (!data) {
    return null;
  }

  let selectedNamespace: FormSettingNamespace;
  let selectedSection: Section;
  data.forEach((namespace) => {
    namespace.sections.forEach((section) => {
      if (section.handle === sectionHandle) {
        selectedNamespace = namespace;
        selectedSection = section;
      }
    });
  });

  if (!selectedNamespace || !selectedSection) {
    if (sectionHandle === TAB_USAGE) {
      return <FormUsage />;
    }

    return null;
  }

  const { properties } = selectedNamespace;

  return (
    <FormSettingsContainer>
      <Breadcrumb
        id="sub-settings"
        label={selectedSection.label}
        url={currentPath.pathname}
      />
      <SectionHeader>{translate(selectedSection?.label)}</SectionHeader>

      <SectionContainer>
        {properties
          .filter((property) => property.section === selectedSection?.handle)
          .filter((property) => property.visible)
          .map((property) => (
            <FieldComponent
              key={property.handle}
              namespace={selectedNamespace.handle}
              property={property}
            />
          ))}
      </SectionContainer>
    </FormSettingsContainer>
  );
};
