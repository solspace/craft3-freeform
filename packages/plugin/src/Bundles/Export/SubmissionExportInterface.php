<?php

namespace Solspace\Freeform\Bundles\Export;

use craft\elements\db\ElementQueryInterface;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;

interface SubmissionExportInterface
{
    public const EVENT_PREPARE_EXPORT_VALUE = 'prepare-export-value';

    public function __construct(
        Form $form,
        ElementQueryInterface $query,
        FieldDescriptorCollection $fieldDescriptors,
        ExportSettings $settings
    );

    public static function getLabel(): string;

    public function getMimeType(): string;

    public function getFileExtension(): string;

    public function getSettings(): ExportSettings;

    public function export($resource): void;
}
