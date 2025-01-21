<?php

namespace Solspace\Freeform\Library\DataObjects;

class ExportSettings
{
    public function __construct(
        private bool $removeNewlines = false,
        private bool $exportLabels = false,
        private ?string $timezone = null,
        private bool $handlesAsNames = false
    ) {}

    public function isRemoveNewlines(): bool
    {
        return $this->removeNewlines;
    }

    public function setRemoveNewlines(bool $removeNewlines): self
    {
        $this->removeNewlines = $removeNewlines;

        return $this;
    }

    public function isExportLabels(): bool
    {
        return $this->exportLabels;
    }

    public function setExportLabels(bool $exportLabels): self
    {
        $this->exportLabels = $exportLabels;

        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function isHandlesAsNames(): bool
    {
        return $this->handlesAsNames;
    }

    public function setHandlesAsNames(bool $handlesAsNames): self
    {
        $this->handlesAsNames = $handlesAsNames;

        return $this;
    }
}
