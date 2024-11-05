<?php

namespace Solspace\Freeform\Bundles\Backup\Collections;

use Solspace\Freeform\Bundles\Backup\Collections\Templates\FileTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\NotificationTemplateCollection;
use Solspace\Freeform\Bundles\Backup\Collections\Templates\PdfTemplateCollection;

class TemplateCollection
{
    private ?NotificationTemplateCollection $notification = null;
    private ?PdfTemplateCollection $pdf = null;
    private ?FileTemplateCollection $formatting = null;
    private ?FileTemplateCollection $success = null;

    public function count(): int
    {
        return $this->notification?->count() + $this->formatting?->count() + $this->success?->count();
    }

    public function getNotification(): ?NotificationTemplateCollection
    {
        return $this->notification;
    }

    public function setNotification(?NotificationTemplateCollection $notificationTemplates): self
    {
        $this->notification = $notificationTemplates;

        return $this;
    }

    public function getPdf(): ?PdfTemplateCollection
    {
        return $this->pdf;
    }

    public function setPdf(?PdfTemplateCollection $pdf): self
    {
        $this->pdf = $pdf;

        return $this;
    }

    public function getFormatting(): ?FileTemplateCollection
    {
        return $this->formatting;
    }

    public function setFormatting(?FileTemplateCollection $formattingTemplates): self
    {
        $this->formatting = $formattingTemplates;

        return $this;
    }

    public function getSuccess(): ?FileTemplateCollection
    {
        return $this->success;
    }

    public function setSuccess(?FileTemplateCollection $successTemplates): self
    {
        $this->success = $successTemplates;

        return $this;
    }
}
