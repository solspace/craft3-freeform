import React from 'react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import ReactDOM from 'react-dom/client';
import { Provider } from 'react-redux';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { Breadcrumb } from '@components/breadcrumbs/breadcrumbs';
import { BreadcrumbProvider } from '@components/breadcrumbs/breadcrumbs.context';
import { CpNavigation } from '@components/cp-navigation/cp-navigation';
import { ZIndexContextProvider } from '@components/form-controls/context/z-index.context';
import { ModalProvider } from '@components/modals/modal.context';
import { queryClient } from '@config/react-query';
import { PortalProvider } from '@editor/builder/contexts/portal.context';
import { store } from '@editor/store';
import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';

import '../config';

import { Form, Forms } from './app/pages/forms';
import { ImportExport } from './app/pages/import-export';
import { ExportFreeform } from './app/pages/import-export/export/views/freeform/freeform';
import { ImportExpressForms } from './app/pages/import-export/import/express-forms/express-forms';
import { ImportFreeformData } from './app/pages/import-export/import/freeform-data/freeform-data';
import { LimitedUsers } from './app/pages/limited-users/limited-users';
import { LimitedUsersDetail } from './app/pages/limited-users/limited-users.detail';
import { SurveyResults } from './app/pages/surveys/results/results';
import { Welcome } from './app/pages/welcome/welcome';
import { EscapeStackProvider } from './contexts/escape/escape.context';
import { SiteProvider } from './contexts/site/site.context';
import ManualStyles from './styles/manual';
import { debug } from './utils/debug';
import { generateUrl } from './utils/urls';
import App from './App';

import './styles.css';

const container = document.getElementById('freeform-client');
const root = ReactDOM.createRoot(container);

debug.log(
  '%c\n' +
    '  ███████╗██████╗ ███████╗███████╗███████╗ ██████╗ ██████╗ ███╗   ███╗\n' +
    '  ██╔════╝██╔══██╗██╔════╝██╔════╝██╔════╝██╔═══██╗██╔══██╗████╗ ████║\n' +
    '  █████╗  ██████╔╝█████╗  █████╗  █████╗  ██║   ██║██████╔╝██╔████╔██║\n' +
    '  ██╔══╝  ██╔══██╗██╔══╝  ██╔══╝  ██╔══╝  ██║   ██║██╔══██╗██║╚██╔╝██║\n' +
    '  ██║     ██║  ██║███████╗███████╗██║     ╚██████╔╝██║  ██║██║ ╚═╝ ██║\n' +
    '  ╚═╝     ╚═╝  ╚═╝╚══════╝╚══════╝╚═╝      ╚═════╝ ╚═╝  ╚═╝╚═╝     ╚═╝\n',
  debug.colors.blue
);

root.render(
  <DndProvider backend={HTML5Backend}>
    <BrowserRouter basename={generateUrl('/', false)}>
      <QueryClientProvider client={queryClient}>
        <SiteProvider>
          <ZIndexContextProvider>
            <EscapeStackProvider>
              <BreadcrumbProvider>
                <Provider store={store}>
                  <PortalProvider>
                    <ModalProvider>
                      <Breadcrumb id="root" label="Freeform" url="/forms" />
                      <ManualStyles />
                      <ReactQueryDevtools />
                      <CpNavigation />
                      <Routes>
                        <Route path="/" element={<App />}>
                          <Route path="forms">
                            <Route path=":formId/*" element={<Form />} />
                            <Route index element={<Forms />} />
                          </Route>
                          <Route
                            path="/surveys/:handle"
                            element={<SurveyResults />}
                          />
                          <Route path="welcome" element={<Welcome />} />
                          <Route path="import" element={<ImportExport />}>
                            <Route
                              path="forms"
                              element={<ImportFreeformData />}
                            />
                            <Route
                              path="express-forms"
                              element={<ImportExpressForms />}
                            />
                          </Route>
                          <Route path="export" element={<ImportExport />}>
                            <Route path="forms" element={<ExportFreeform />} />
                          </Route>
                          <Route path="settings/limited-users">
                            <Route
                              path=":id"
                              element={<LimitedUsersDetail />}
                            />
                            <Route index element={<LimitedUsers />} />
                          </Route>
                        </Route>
                      </Routes>
                    </ModalProvider>
                  </PortalProvider>
                </Provider>
              </BreadcrumbProvider>
            </EscapeStackProvider>
          </ZIndexContextProvider>
        </SiteProvider>
      </QueryClientProvider>
    </BrowserRouter>
  </DndProvider>
);
