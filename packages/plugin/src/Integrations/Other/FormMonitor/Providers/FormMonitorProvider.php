<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Providers;

use craft\web\Request;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;

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

        $formMonitor = $this->integrationsProvider->getFirstForForm($form, FormMonitor::class);
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
