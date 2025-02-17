import React from 'react';
import { Route, Routes } from 'react-router-dom';

import { FormMonitor } from './tabs/form-monitor/monitor';
import { FMResults } from './tabs/form-monitor/results/results';
import { FormSettings } from './tabs/form-settings/settings';
import { SettingsEditor } from './tabs/form-settings/settings.editor';
import { Integrations } from './tabs/integrations/integrations';
import { PropertyEditor as IntegrationsEditor } from './tabs/integrations/property-editor/property-editor';
import { LayoutEditor } from './tabs/layout/layout';
import { Notifications } from './tabs/notifications/notifications';
import { PropertyEditor as NotificationsEditor } from './tabs/notifications/property-editor/property-editor';
import { ButtonRulesEditor } from './tabs/rules/editor/button.editor';
import { RulesEmpty } from './tabs/rules/editor/editor.empty';
import { FieldRulesEditor } from './tabs/rules/editor/field.editor';
import { PageRulesEditor } from './tabs/rules/editor/page.editor';
import { SubmitFormRulesEditor } from './tabs/rules/editor/submit-form.editor';
import { Rules } from './tabs/rules/rules';
import { Tabs } from './tabs/tabs';
import { BuilderContent, BuilderWrapper } from './builder.styles';

export const Builder: React.FC = () => {
  return (
    <BuilderWrapper>
      <Tabs />
      <BuilderContent>
        <Routes>
          <Route index element={<LayoutEditor />} />
          <Route path="notifications" element={<Notifications />}>
            <Route path=":uid?" element={<NotificationsEditor />} />
          </Route>
          <Route path="integrations" element={<Integrations />}>
            <Route path=":id?/:handle?" element={<IntegrationsEditor />} />
          </Route>
          <Route path="rules" element={<Rules />}>
            <Route index element={<RulesEmpty />} />
            <Route path="field/:uid" element={<FieldRulesEditor />} />
            <Route path="page/:uid" element={<PageRulesEditor />} />
            <Route
              path="page/:uid/buttons/:button"
              element={<ButtonRulesEditor />}
            />
            <Route path="submit" element={<SubmitFormRulesEditor />} />
          </Route>
          <Route path="settings" element={<FormSettings />}>
            <Route index element={<SettingsEditor />} />
            <Route path=":sectionHandle" element={<SettingsEditor />} />
          </Route>
          <Route path="form-monitor" element={<FormMonitor />}>
            <Route index element={<FMResults />} />
          </Route>
        </Routes>
      </BuilderContent>
    </BuilderWrapper>
  );
};
