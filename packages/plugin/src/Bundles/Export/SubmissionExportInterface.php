<?php

namespace Solspace\Freeform\Bundles\Export;

use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;

interface SubmissionExportInterface
{
    public const EVENT_PREPARE_EXPORT_COLUMN = 'prepare-export-column';

    public function __construct(
        Form $form,
        SubmissionQuery $query,
        FieldDescriptorCollection $fieldDescriptors,
        ExportSettings $settings
    );

    public static function getLabel(): string;

    public function getMimeType(): string;

    public function getFileExtension(): string;

    public function export(): mixed;
}
