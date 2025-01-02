<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Providers;

use craft\db\Query;
use craft\web\Request;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\IntegrationRecord;

class FormMonitorProvider
{
    private const HEADER_FORM_ID = 'X-Form-Monitor-Form-Id';
    private const HEADER_TOKEN = 'X-Form-Monitor-Token';
    private const HEADER_REQUEST_ID = 'X-Form-Monitor-Request-Id';
    private null|bool|Request $request = null;
    private array $requestCache = [];

    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
    ) {}

    public function isRequestFromFormMonitor(Form $form): bool
    {
        if (!\array_key_exists($form->getId(), $this->requestCache)) {
            $this->requestCache[$form->getId()] = $this->handleRequest($form);
        }

        return $this->requestCache[$form->getId()];
    }

    public function getRequestId(Form $form): ?string
    {
        if (!$this->isRequestFromFormMonitor($form)) {
            return null;
        }

        return $this->getRequest()->getHeaders()->get(self::HEADER_REQUEST_ID);
    }

    public function isFormMonitorEnabled(): bool
    {
        try {
            return (bool) (new Query())
                ->select('fi.[[id]]')
                ->from(FormIntegrationRecord::TABLE.' fi')
                ->innerJoin(IntegrationRecord::TABLE.' i', 'i.[[id]] = fi.[[integrationId]]')
                ->where([
                    'fi.[[enabled]]' => true,
                    'i.[[class]]' => FormMonitor::class,
                ])
                ->count()
            ;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getFormMonitor(Form $form): ?FormMonitor
    {
        return $this->integrationsProvider->getFirstForForm($form, FormMonitor::class);
    }

    private function handleRequest(Form $form): bool
    {
        $request = $this->getRequest();
        if (false === $request) {
            return false;
        }

        $headers = $request->getHeaders();

        $formId = $headers->get(self::HEADER_FORM_ID);
        if (!$formId || (int) $formId !== $form->getId()) {
            return false;
        }

        $token = $headers->get(self::HEADER_TOKEN);
        if (!$token) {
            return false;
        }

        $formMonitor = $this->getFormMonitor($form);
        if (!$formMonitor) {
            return false;
        }

        return $formMonitor->getRequestToken() === $token;
    }

    private function getRequest(): bool|Request
    {
        if (null === $this->request) {
            $request = \Craft::$app->getRequest();

            if ($request instanceof Request) {
                $this->request = $request;
            } else {
                $this->request = false;
            }
        }

        return $this->request;
    }
}
