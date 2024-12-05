<?php

namespace Solspace\Freeform\Library\Export;

use Carbon\Carbon;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;
use Solspace\Freeform\Fields\Interfaces\OptionsInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;
use Solspace\Freeform\Library\Export\Objects\Column;
use Solspace\Freeform\Library\Export\Objects\Row;

abstract class AbstractExport implements ExportInterface
{
    /** @var Row[] */
    private array $rows;

    private ?string $timezone;
    private bool $removeNewLines;
    private bool $exportLabels;
    private bool $handlesAsNames;

    public function __construct(
        private Form $form,
        array $submissionData,
        ?ExportSettings $settings = null
    ) {
        if (null === $settings) {
            $settings = new ExportSettings();
        }

        $this->timezone = $settings->getTimezone();

        $this->removeNewLines = $settings->isRemoveNewlines();
        $this->exportLabels = $settings->isExportLabels();
        $this->handlesAsNames = $settings->isHandlesAsNames();

        $this->rows = $this->parseSubmissionDataIntoRows($submissionData);
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * @return Row[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function isRemoveNewLines(): bool
    {
        return $this->removeNewLines;
    }

    public function isHandlesAsNames(): bool
    {
        return $this->handlesAsNames;
    }

    /**
     * Prepare the submission data to have field handles and labels ready.
     */
    private function parseSubmissionDataIntoRows(array $submissionData): array
    {
        $reservedFields = [
            'id',
            'dateCreated',
            'ip',
            'cc_type',
            'cc_amount',
            'cc_currency',
            'cc_card',
            'cc_status',
        ];

        $form = $this->getForm();

        $rows = [];
        foreach ($submissionData as $rowIndex => $row) {
            $rowObject = new Row();

            $columnIndex = 0;
            foreach ($row as $fieldId => $value) {
                $field = null;
                if ('dateCreated' === $fieldId) {
                    $date = new Carbon($value, 'UTC');
                    if ($this->timezone) {
                        $date->setTimezone($this->timezone);
                    }

                    $value = $date->toDateTimeString();
                }

                $label = $fieldId;
                $handle = $fieldId;

                $field = !\in_array($fieldId, $reservedFields, true) ? $form->get($fieldId) : null;
                if (null !== $field) {
                    $label = $field->getLabel();
                    $handle = $field->getHandle();

                    if ($field instanceof MultiValueInterface) {
                        if (\is_string($value) && preg_match('/^(\[|\{).*(\]|\})$/', $value)) {
                            $value = (array) json_decode($value, true);
                        }
                    }

                    if ($field instanceof FileUploadField && \is_array($value)) {
                        $urls = [];

                        foreach ($value as $assetId) {
                            $asset = \Craft::$app->assets->getAssetById((int) $assetId);
                            if ($asset) {
                                $assetValue = $asset->filename;
                                if ($asset->getUrl()) {
                                    $assetValue = $asset->getUrl();
                                }

                                $urls[] = $assetValue;
                            }
                        }

                        $value = $urls;
                    }

                    if ($field instanceof TextareaField && $this->isRemoveNewLines()) {
                        $value = trim(preg_replace('/\s+/', ' ', $value));
                    }

                    if ($this->exportLabels && $field instanceof OptionsInterface) {
                        $options = $field->getOptions();

                        if (\is_array($value)) {
                            foreach ($value as $index => $val) {
                                $value[$index] = $options->getOption($val)?->getLabel() ?? $val;
                            }
                        } else {
                            $value = $options->getOption($value)?->getLabel() ?? $value;
                        }
                    }
                } else {
                    $label = match ($fieldId) {
                        'id' => 'ID',
                        'dateCreated' => 'Date Created',
                        'ip' => 'IP Address',
                        'cc_type' => 'Payment Type',
                        'cc_amount' => 'Payment Amount',
                        'cc_currency' => 'Payment Currency',
                        'cc_card' => 'Payment Card',
                        'cc_status' => 'Payment Status',
                        default => ucfirst($label),
                    };
                }

                $rowObject->addColumn(
                    new Column($columnIndex++, $label, $handle, $field, $value)
                );
            }

            $rows[] = $rowObject;
        }

        return $rows;
    }
}
