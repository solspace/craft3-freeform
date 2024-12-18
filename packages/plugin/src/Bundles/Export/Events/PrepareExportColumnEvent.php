<?php

namespace Solspace\Freeform\Bundles\Export\Events;

use craft\base\Event;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Elements\Submission;

class PrepareExportColumnEvent extends Event
{
    public function __construct(
        private SubmissionExportInterface $exporter,
        private FieldDescriptor $fieldDescriptor,
        private Submission $submission,
        private mixed $value,
        private mixed $key,
    ) {
        parent::__construct();
    }

    public function getExporter(): SubmissionExportInterface
    {
        return $this->exporter;
    }

    public function getFieldDescriptor(): FieldDescriptor
    {
        return $this->fieldDescriptor;
    }

    public function getSubmission(): Submission
    {
        return $this->submission;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getKey(): mixed
    {
        return $this->key;
    }

    public function setKey(mixed $key): void
    {
        $this->key = $key;
    }
}
