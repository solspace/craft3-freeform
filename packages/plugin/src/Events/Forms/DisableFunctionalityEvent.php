<?php

namespace Solspace\Freeform\Events\Forms;

use Solspace\Freeform\Events\ArrayableEvent;
use Solspace\Freeform\Form\Form;

class DisableFunctionalityEvent extends ArrayableEvent
{
    public function __construct(
        private Form $form,
        private null|array|bool $settings = []
    ) {
        parent::__construct();
    }

    public function fields(): array
    {
        return ['form', 'settings'];
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getSettings(): null|array|bool
    {
        return $this->settings;
    }

    public function setSettings(null|array|bool $settings): self
    {
        $this->settings = $settings;

        return $this;
    }
}
