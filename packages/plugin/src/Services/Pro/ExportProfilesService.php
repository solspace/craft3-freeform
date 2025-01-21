<?php

namespace Solspace\Freeform\Services\Pro;

use craft\db\Query;
use JetBrains\PhpStorm\NoReturn;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Implementations\Excel\ExportExcel;
use Solspace\Freeform\Bundles\Export\Implementations\Json\ExportJson;
use Solspace\Freeform\Bundles\Export\Implementations\Text\ExportText;
use Solspace\Freeform\Bundles\Export\Implementations\Xml\ExportXml;
use Solspace\Freeform\Bundles\Export\SubmissionExportInterface;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Events\Export\Profiles\DeleteEvent;
use Solspace\Freeform\Events\Export\Profiles\RegisterExporterEvent;
use Solspace\Freeform\Events\Export\Profiles\SaveEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\ExportSettings;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Models\Pro\ExportProfileModel;
use Solspace\Freeform\Records\Pro\ExportProfileRecord;
use yii\base\Component;
use yii\web\HttpException;

class ExportProfilesService extends Component
{
    public const EVENT_BEFORE_SAVE = 'beforeSave';
    public const EVENT_AFTER_SAVE = 'afterSave';
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    public const EVENT_AFTER_DELETE = 'afterDelete';
    public const EVENT_REGISTER_EXPORTER = 'registerExporter';

    /** @var ExportProfileModel[] */
    private static $profileCache;
    private static $allProfilesLoaded;
    private static $exporters;

    public function getExporters(): array
    {
        if (null === self::$exporters) {
            $event = new RegisterExporterEvent();

            $event
                ->addExporter('excel', ExportExcel::class)
                ->addExporter('csv', ExportCsv::class)
                ->addExporter('json', ExportJson::class)
                ->addExporter('xml', ExportXml::class)
                ->addExporter('text', ExportText::class)
            ;

            $this->trigger(self::EVENT_REGISTER_EXPORTER, $event);

            self::$exporters = $event->getExporters();
        }

        return self::$exporters;
    }

    public function getExporterTypes(): array
    {
        $types = [];
        foreach ($this->getExporters() as $type => $exporterClass) {
            $types[$type] = $exporterClass::getLabel();
        }

        return $types;
    }

    /**
     * @return ExportProfileModel[]
     */
    public function getAllProfiles(): array
    {
        if (null === self::$profileCache || !self::$allProfilesLoaded) {
            self::$profileCache = [];

            $items = $this->getQuery()->all();
            foreach ($items as $data) {
                $model = $this->createExportProfile($data);

                self::$profileCache[$model->id] = $model;
            }

            self::$allProfilesLoaded = true;
        }

        return self::$profileCache;
    }

    public function getAllNamesById(): array
    {
        return $this
            ->getQuery()
            ->select('name')
            ->indexBy('id')
            ->column()
        ;
    }

    public function getProfileById(int $id): ?ExportProfileModel
    {
        if (null === self::$profileCache || !isset(self::$profileCache[$id])) {
            if (null === self::$profileCache) {
                self::$profileCache = [];
            }

            $data = $this->getQuery()
                ->where(['id' => $id])
                ->one()
            ;

            $model = null;
            if ($data) {
                $model = $this->createExportProfile($data);
            }

            self::$profileCache[$id] = $model;
        }

        return self::$profileCache[$id];
    }

    public function save(ExportProfileModel $model): bool
    {
        $isNew = !$model->id;

        if (!$isNew) {
            $record = ExportProfileRecord::findOne(['id' => $model->id]);

            if (!$record) {
                throw new HttpException(Freeform::t('Export Profile with ID {id} not found', ['id' => $model->id]));
            }
        } else {
            $record = new ExportProfileRecord();
        }

        $record->name = $model->name;
        $record->formId = $model->formId;
        $record->limit = $model->limit;
        $record->dateRange = $model->dateRange;
        $record->rangeStart = $model->rangeStart;
        $record->rangeEnd = $model->rangeEnd;
        $record->fields = $model->fields;
        $record->filters = $model->filters;
        $record->statuses = $model->statuses;

        $record->validate();
        $model->addErrors($record->getErrors());

        $beforeSaveEvent = new SaveEvent($model, $isNew);
        $this->trigger(self::EVENT_BEFORE_SAVE, $beforeSaveEvent);

        if ($beforeSaveEvent->isValid && !$model->hasErrors()) {
            $transaction = \Craft::$app->db->beginTransaction();

            try {
                $record->save(false);

                if (!$model->id) {
                    $model->id = $record->id;
                }

                self::$profileCache[$model->id] = $model;

                $transaction?->commit();

                $this->trigger(self::EVENT_AFTER_SAVE, new SaveEvent($model, $isNew));

                return true;
            } catch (\Exception $e) {
                $transaction?->rollBack();

                throw $e;
            }
        }

        return false;
    }

    public function deleteById($id): bool
    {
        $model = $this->getProfileById($id);

        if (!$model) {
            return false;
        }

        $beforeDeleteEvent = new DeleteEvent($model);
        $this->trigger(self::EVENT_BEFORE_DELETE, $beforeDeleteEvent);

        if (!$beforeDeleteEvent->isValid) {
            return false;
        }

        $transaction = \Craft::$app->db->beginTransaction();

        try {
            $affectedRows = \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(ExportProfileRecord::TABLE, ['id' => $model->id])
                ->execute()
            ;

            $transaction?->commit();

            $this->trigger(self::EVENT_AFTER_DELETE, new DeleteEvent($model));

            return (bool) $affectedRows;
        } catch (\Exception $exception) {
            $transaction?->rollBack();

            throw $exception;
        }
    }

    public function getExportSettings(): ExportSettings
    {
        $settings = Freeform::getInstance()->settings;

        return new ExportSettings(
            $settings->isRemoveNewlines(),
            $settings->getSettingsModel()->exportLabels,
            \Craft::$app->projectConfig->get('plugins.freeform.export.timezone') ?? date_default_timezone_get(),
            $settings->getSettingsModel()->exportHandlesAsNames
        );
    }

    public function createExporter(
        string $type,
        Form $form,
        SubmissionQuery $query,
        FieldDescriptorCollection $fieldDescriptors
    ): SubmissionExportInterface {
        $exporters = $this->getExporters();
        if (!isset($exporters[$type])) {
            throw new FreeformException("Cannot export type `{$type}`");
        }

        $class = $exporters[$type];

        return new $class($form, $query, $fieldDescriptors, $this->getExportSettings());
    }

    #[NoReturn]
    public function export(SubmissionExportInterface $exporter, Form $form): void
    {
        $fileName = \sprintf(
            '%s submissions %s.%s',
            $form->getName(),
            date('Y-m-d H:i', time()),
            $exporter->getFileExtension()
        );

        $resource = tmpfile();
        $exporter->export($resource);

        $this->outputFile($resource, $fileName, $exporter->getMimeType());
    }

    #[NoReturn]
    public function outputFile($file, string $fileName, string $contentType): void
    {
        rewind($file);

        \Craft::$app
            ->response
            ->setDownloadHeaders($fileName, $contentType)
            ->sendStreamAsFile($file, $fileName)
            ->send()
        ;

        exit;
    }

    private function getQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'export_profiles.id',
                    'export_profiles.formId',
                    'export_profiles.name',
                    'export_profiles.limit',
                    'export_profiles.dateRange',
                    'export_profiles.rangeStart',
                    'export_profiles.rangeEnd',
                    'export_profiles.fields',
                    'export_profiles.filters',
                    'export_profiles.statuses',
                ]
            )
            ->from(ExportProfileRecord::TABLE.' export_profiles')
            ->orderBy(['export_profiles.id' => \SORT_ASC])
        ;
    }

    private function createExportProfile(array $data): ExportProfileModel
    {
        $exportProfile = new ExportProfileModel($data);

        $jsonColumns = ['fields', 'filters', 'statuses'];
        foreach ($jsonColumns as $column) {
            $value = $exportProfile->{$column};
            if (\is_string($value) && '' !== $value && '*' !== $value) {
                $exportProfile->{$column} = json_decode($value, true);
            }
        }

        return $exportProfile;
    }
}
