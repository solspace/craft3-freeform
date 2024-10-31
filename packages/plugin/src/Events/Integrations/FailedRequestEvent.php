<?php

namespace Solspace\Freeform\Events\Integrations;

use craft\events\CancelableEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;

class FailedRequestEvent extends CancelableEvent
{
    private bool $retry = false;

    public function __construct(
        private ?Form $form,
        private IntegrationInterface $integration,
        private \Exception $exception,
    ) {
        parent::__construct();
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function getIntegration(): IntegrationInterface
    {
        return $this->integration;
    }

    public function getException(): \Exception
    {
        return $this->exception;
    }

    public function isRetry(): bool
    {
        return $this->retry;
    }

    public function triggerRetry(): void
    {
        $this->retry = true;
    }
}
