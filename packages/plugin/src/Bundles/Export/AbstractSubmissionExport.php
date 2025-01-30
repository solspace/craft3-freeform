<?php

namespace Solspace\Freeform\Bundles\Export;

use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use Solspace\Freeform\Bundles\Backup\DTO\Submission;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Events\PrepareExportValueEvent;
use Solspace\Freeform\Bundles\Export\Objects\Column;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;
use yii\base\Event;

abstract class AbstractSubmissionExport implements SubmissionExportInterface
{
    private ?string $timezone;

    public function __construct(
        private Form $form,
        private ElementQueryInterface $query,
        private FieldDescriptorCollection $fieldDescriptors,
        private ?ExportSettings $settings = null,
    ) {
        if (null === $settings) {
            $this->settings = new ExportSettings();
        }

        $this->timezone = $this->settings->getTimezone();
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getFieldDescriptors(): FieldDescriptorCollection
    {
        return $this->fieldDescriptors;
    }

    public function getSettings(): ExportSettings
    {
        return $this->settings;
    }

    public function getQuery(): ElementQueryInterface
    {
        return $this->query;
    }

    /**
     * @return Column[][][]|\Generator
     */
    public function getRowBatch(int $size = 100): \Generator
    {
        $query = $this->getQuery();

        /** @var Submission[] $elements */
        foreach ($query->batch($size) as $elements) {
            $rows = [];

            /** @var ElementInterface $element */
            foreach ($elements as $element) {
                $index = 0;
                $columns = [];
                foreach ($this->getFieldDescriptors() as $descriptor) {
                    if (!$descriptor->isUsed()) {
                        continue;
                    }

                    $value = $element->{$descriptor->getId()};
                    $field = $value instanceof FieldInterface ? $value : null;

                    if ($field) {
                        $value = $field->getValue();
                    }

                    $event = new PrepareExportValueEvent($this, $descriptor, $element, $field, $value);
                    Event::trigger($this, self::EVENT_PREPARE_EXPORT_VALUE, $event);

                    $columns[] = new Column(
                        $index++,
                        $descriptor,
                        $field,
                        $event->getValue(),
                    );
                }

                $rows[] = $columns;
            }

            yield $rows;
        }
    }
}
