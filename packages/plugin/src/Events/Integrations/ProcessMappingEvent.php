<?php

namespace Solspace\Freeform\Events\Integrations;

use Solspace\Freeform\Events\CancelableArrayableEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Integrations\IntegrationInterface;

class ProcessMappingEvent extends CancelableArrayableEvent
{
    public function __construct(
        private IntegrationInterface $integration,
        private Form $form,
        private array $fields,
        private array $mappedValues = [],
    ) {
        parent::__construct();
    }

    public function fields(): array
    {
        return ['integration', 'form', 'fields', 'mappedValues'];
    }

    public function getIntegration(): IntegrationInterface
    {
        return $this->integration;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getMappedValues(): array
    {
        return $this->mappedValues;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function setMappedValues(array $mappedValues): self
    {
        $this->mappedValues = $mappedValues;

        return $this;
    }
}
