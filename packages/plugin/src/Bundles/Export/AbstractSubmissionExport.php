<?php

namespace Solspace\Freeform\Bundles\Export;

use Carbon\Carbon;
use Solspace\Freeform\Bundles\Backup\DTO\Submission;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Events\PrepareExportColumnEvent;
use Solspace\Freeform\Bundles\Export\Objects\Column;
use Solspace\Freeform\Bundles\Export\Objects\Row;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\FileUploadField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;
use Solspace\Freeform\Fields\Interfaces\OptionsInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;
use yii\base\Event;

abstract class AbstractSubmissionExport implements SubmissionExportInterface
{
    private ?string $timezone;

    public function __construct(
        private Form $form,
        private SubmissionQuery $query,
        private FieldDescriptorCollection $fieldDescriptors,
        private ?ExportSettings $settings = null
    ) {
        if (null === $settings) {
            $settings = new ExportSettings();
        }

        $this->timezone = $settings->getTimezone();
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getFieldDescriptors(): FieldDescriptorCollection
    {
        return $this->fieldDescriptors;
    }

    public function getSettings(): ?ExportSettings
    {
        return $this->settings;
    }

    protected function getQuery(): SubmissionQuery
    {
        return $this->query;
    }

    protected function getRowBatch(int $size = 100): \Generator
    {
        $query = $this->getQuery();

        /** @var Submission[] $elements */
        foreach ($query->batch($size) as $elements) {
            $rows = [];
            foreach ($elements as $element) {
                $columns = [];
                $index = 0;
                foreach ($this->getFieldDescriptors() as $descriptor) {
                    $value = $element->{$descriptor->getId()};
                    if ($value instanceof FieldInterface) {
                        $value = $value->getValue();
                    }

                    $event = new PrepareExportColumnEvent(
                        $this,
                        $descriptor,
                        $element,
                        $value,
                        $index++,
                    );

                    Event::trigger(
                        SubmissionExportInterface::class,
                        SubmissionExportInterface::EVENT_PREPARE_EXPORT_COLUMN,
                        $event
                    );

                    $columns[$event->getKey()] = $event->getValue();
                }

                $rows[] = $columns;
            }

            yield $rows;
        }
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
