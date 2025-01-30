<?php

namespace Solspace\Freeform\Bundles\Export\Events;

use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\FieldInterface;
use yii\base\Event;

class PrepareExportValueEvent extends Event
{
    public function __construct(
        private SubmissionExportInterface $exporter,
        private FieldDescriptor $descriptor,
        private Submission $submission,
        private ?FieldInterface $field,
        private mixed $value,
    ) {
        parent::__construct();
    }

    public function getExporter(): SubmissionExportInterface
    {
        return $this->exporter;
    }

    public function getDescriptor(): FieldDescriptor
    {
        return $this->descriptor;
    }

    public function getSubmission(): Submission
    {
        return $this->submission;
    }

    public function getField(): ?FieldInterface
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }
}
