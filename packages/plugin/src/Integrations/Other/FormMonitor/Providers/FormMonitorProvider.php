<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Providers;

use craft\web\Request;
use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\FormMonitor;

class FormMonitorProvider
{
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

    private function handleRequest(Form $form): bool
    {
        $request = $this->getRequest();
        if (false === $request) {
            return false;
        }

        $headers = $request->getHeaders();

        $formId = $headers->get('X-Form-Monitor-Form-Id');
        if (!$formId || (int) $formId !== $form->getId()) {
            return false;
        }

        $token = $headers->get('X-Form-Monitor-Token');
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
