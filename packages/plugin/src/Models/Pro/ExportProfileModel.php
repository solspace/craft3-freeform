<?php

namespace Solspace\Freeform\Models\Pro;

use Carbon\Carbon;
use craft\base\Model;
use craft\helpers\Db;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;

class ExportProfileModel extends Model
{
    public const RANGE_TODAY = 'today';
    public const RANGE_YESTERDAY = 'yesterday';
    public const RANGE_CUSTOM = 'custom';

    public ?int $id = null;
    public ?int $formId = null;
    public ?string $name = null;
    public ?int $limit = null;
    public ?string $dateRange = null;
    public ?string $rangeStart = null;
    public ?string $rangeEnd = null;
    public ?array $fields = null;
    public ?array $filters = null;
    public null|array|string $statuses = null;

    public static function create(Form $form): self
    {
        $model = new self();

        $model->formId = $form->getId();
        $model->statuses = '*';

        return $model;
    }

    public function getForm(): Form
    {
        return Freeform::getInstance()->forms->getFormById($this->formId);
    }

    public function getSubmissionCount(): int
    {
        $query = $this->getQuery();

        try {
            return $query->count();
        } catch (\Exception $e) {
            \Craft::$app->session->setError($e->getMessage());

            return 0;
        }
    }

    public function getDateRangeEnd(): ?Carbon
    {
        $timezone = $this->getTimezoneOverride();

        return match ($this->dateRange) {
            self::RANGE_CUSTOM => (new Carbon($this->rangeEnd, $timezone))->setTime(23, 59, 59),
            self::RANGE_YESTERDAY => (new Carbon('-1 day', $timezone))->setTime(23, 59, 59),
            default => null,
        };
    }

    public function getDateRangeStart(): ?Carbon
    {
        if (empty($this->dateRange)) {
            return null;
        }

        $timezone = $this->getTimezoneOverride();

        if (is_numeric($this->dateRange)) {
            return (new Carbon("-{$this->dateRange} days", $timezone))->setTime(0, 0, 0);
        }

        return match ($this->dateRange) {
            self::RANGE_CUSTOM => (new Carbon($this->rangeStart, $timezone))->setTime(0, 0, 0),
            self::RANGE_YESTERDAY => (new Carbon('-1 day', $timezone))->setTime(0, 0, 0),
            default => (new Carbon('now', $timezone))->setTime(0, 0, 0),
        };
    }

    public function getFieldDescriptors(): FieldDescriptorCollection
    {
        $form = $this->getForm();
        $collection = new FieldDescriptorCollection();

        if (!empty($this->fields)) {
            foreach ($this->fields as $fieldId => $item) {
                $label = $item['label'];
                $isChecked = (bool) $item['checked'];

                if (is_numeric($fieldId)) {
                    $field = $form->get($fieldId);
                    if (!$field) {
                        continue;
                    }

                    $label = $field->getLabel();
                }

                $collection->add(
                    new FieldDescriptor(
                        $fieldId,
                        $label,
                        $isChecked,
                    )
                );
            }
        }

        if (0 === $collection->count()) {
            $collection
                ->add(new FieldDescriptor('id', 'ID'))
                ->add(new FieldDescriptor('title', 'Title'))
                ->add(new FieldDescriptor('ip', 'IP Address'))
                ->add(new FieldDescriptor('dateCreated', 'Date Created'))
                ->add(new FieldDescriptor('status', 'Status'))
            ;
        }

        if (!$collection->has('userId')) {
            $collection->add(new FieldDescriptor('userId', 'Author'));
        }

        foreach ($form->getLayout()->getFields() as $field) {
            if (
                $field instanceof NoStorageInterface
                || !$field->getId()
                || $collection->has($field->getId())
            ) {
                continue;
            }

            $id = $field->getId();
            $label = $field->getLabel();

            $collection->add(new FieldDescriptor($id, $label), $id);
        }

        return $collection;
    }

    public function safeAttributes(): array
    {
        return [
            'id',
            'formId',
            'name',
            'limit',
            'dateRange',
            'rangeStart',
            'rangeEnd',
            'fields',
            'statuses',
            'filters',
        ];
    }

    public function getQuery(): SubmissionQuery
    {
        $table = Submission::TABLE;
        $form = $this->getForm();

        $query = Submission::find();
        $query
            ->formId($form->getId())
            ->isSpam(false)
        ;

        $dateRangeStart = $this->getDateRangeStart();
        if ($dateRangeStart) {
            $query->andWhere(['>=', $table.'.[[dateCreated]]', Db::prepareDateForDb($dateRangeStart)]);
        }

        $dateRangeEnd = $this->getDateRangeEnd();
        if ($dateRangeEnd) {
            $query->andWhere(['<=', $table.'.[[dateCreated]]', Db::prepareDateForDb($dateRangeEnd)]);
        }

        if ($this->filters) {
            foreach ($this->filters as $filter) {
                $fieldId = $id = $filter['field'];
                $type = $filter['type'];
                $value = $filter['value'];

                if (is_numeric($id)) {
                    $field = $form->get($id);
                    if (!$field) {
                        continue;
                    }

                    $fieldId = '[['.Submission::getFieldColumnName($field).']]';
                }

                $fieldId = match ($fieldId) {
                    'id' => $table.'.[[id]]',
                    'dateCreated' => $table.'.[[dateCreated]]',
                    'status' => '[[sub_freeform_statuses]].[[name]]',
                    'cc_amount' => 'p.[[amount]]',
                    'cc_currency' => 'p.[[currency]]',
                    'cc_status' => 'p.[[status]]',
                    'cc_card' => 'p.[[last4]]',
                    default => $fieldId,
                };

                match ($type) {
                    '=' => $query->andWhere([$fieldId => $value]),
                    '!=' => $query->andWhere(['!=', $fieldId, $value]),
                    'like' => $query->andWhere(['like', $fieldId, $value, false]),
                    'not-like' => $query->andWhere(['not', ['like', $fieldId, $value, false]])
                };
            }
        }

        $command = (new Query())
            ->select(implode(',', $searchableFields))
            ->from(Submission::TABLE.' s')
            ->innerJoin(StatusRecord::TABLE.' stat', 'stat.[[id]] = s.[[statusId]]')
            ->innerJoin(Submission::getContentTableName($form).' sc', 'sc.[[id]] = s.[[id]]')
            ->where(implode(' AND ', $conditions), $parameters)
            ->andWhere(['s.[[isHidden]]' => false])
        ;

        $siteId = \Craft::$app->sites->currentSite->id;
        if ($isCraft5) {
            $command->innerJoin(
                '{{%elements_sites}} es',
                'es.[[elementId]] = s.[[id]] AND es.[[siteId]] = :siteId',
                ['siteId' => $siteId]
            );
        } else {
            $command->innerJoin(
                '{{%content}} c',
                'c.[[elementId]] = s.[[id]] AND c.[[siteId]] = :siteId',
                ['siteId' => $siteId]
            );
        }

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $elements = Table::ELEMENTS;
            $command->innerJoin(
                "{$elements} e",
                'e.[[id]] = s.[[id]] AND e.[[dateDeleted]] IS NULL'
            );
        }

        if ($this->limit) {
            $query->limit($this->limit);
        }

        if (\is_array($this->statuses)) {
            $query->andWhere(['IN', 'statusId', $this->statuses]);
        }

        return $query;
    }

    private function getTimezoneOverride(): ?string
    {
        static $timezone;
        if (null === $timezone) {
            $timezone = \Craft::$app->projectConfig->get('plugins.freeform.export.timezone') ?? false;
        }

        return $timezone ?: null;
    }
}
