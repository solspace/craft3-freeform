<?php

namespace Solspace\Freeform\Events\Export\Profiles;

use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use yii\base\Event;

class RegisterExporterEvent extends Event
{
    /** @var SubmissionExportInterface[] */
    private $exporters = [];

    /**
     * @return SubmissionExportInterface[]
     */
    public function getExporters(): array
    {
        return $this->exporters;
    }

    /**
     * @throws FreeformException
     * @throws \ReflectionException
     */
    public function addExporter(string $key, string $class): self
    {
        $reflection = new \ReflectionClass($class);
        if (!$reflection->implementsInterface(SubmissionExportInterface::class)) {
            throw new FreeformException('Registered exporter does not implement '.SubmissionExportInterface::class);
        }

        $this->exporters[$key] = $class;

        return $this;
    }
}
