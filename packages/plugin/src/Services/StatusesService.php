<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Services;

use craft\db\Query;
use Solspace\Freeform\Events\Statuses\DeleteEvent;
use Solspace\Freeform\Events\Statuses\SaveEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Database\StatusHandlerInterface;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Models\StatusModel;
use Solspace\Freeform\Records\StatusRecord;

class StatusesService extends BaseService implements StatusHandlerInterface
{
    public const EVENT_BEFORE_SAVE = 'beforeSave';
    public const EVENT_AFTER_SAVE = 'afterSave';
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    public const EVENT_AFTER_DELETE = 'afterDelete';

    /** @var StatusModel[] */
    private static $statusCache = [];

    /** @var StatusModel[] */
    private static $statusByHandleCache = [];
    private static $allStatusesLoaded;

    public function getDefaultStatusId(): int
    {
        return Freeform::getInstance()
            ->settings
            ->getSettingsModel()
            ->defaults
            ->settings
            ->dataStorage
            ->defaultStatus
            ->getValue()
        ;
    }

    /**
     * @return StatusRecord[]
     */
    public function getAllStatuses(bool $indexById = true): array
    {
        if (null === self::$statusCache || !self::$allStatusesLoaded) {
            self::$statusCache = [];

            $results = $this->getStatusQuery()->all();

            foreach ($results as $result) {
                $status = $this->createStatus($result);

                self::$statusCache[$status->id] = $status;
            }

            self::$allStatusesLoaded = true;
        }

        if (!$indexById) {
            return array_values(self::$statusCache);
        }

        return self::$statusCache;
    }

    public function getAllStatusNames(bool $indexById = true): array
    {
        $list = [];
        foreach ($this->getAllStatuses() as $status) {
            if ($indexById) {
                $list[$status->id] = $status->name;
            } else {
                $list[] = $status->name;
            }
        }

        return $list;
    }

    public function getAllStatusIds(): array
    {
        return (new Query())
            ->select(['id'])
            ->from(StatusRecord::TABLE)
            ->orderBy(['name' => \SORT_ASC])
            ->column()
        ;
    }

    public function getStatusById(int $id): ?StatusModel
    {
        if (!isset(self::$statusCache[$id])) {
            $result = $this->getStatusQuery()
                ->where(['id' => $id])
                ->one()
            ;

            $status = null;
            if ($result) {
                $status = $this->createStatus($result);
            }

            self::$statusCache[$id] = $status;
        }

        return self::$statusCache[$id];
    }

    public function getStatusByHandle(string $handle): ?StatusModel
    {
        if (!isset(self::$statusByHandleCache[$handle])) {
            $result = $this->getStatusQuery()
                ->where(['handle' => $handle])
                ->one()
            ;

            $status = null;
            if ($result) {
                $status = $this->createStatus($result);
            }

            self::$statusByHandleCache[$handle] = $status;
        }

        return self::$statusByHandleCache[$handle];
    }

    public function save(StatusModel $model): bool
    {
        $isNew = !$model->id;

        if (!$isNew) {
            $record = StatusRecord::findOne(['id' => $model->id]);
        } else {
            $record = StatusRecord::create();
        }

        $record->name = $model->name;
        $record->handle = $model->handle;
        $record->color = $model->color;
        $record->sortOrder = $model->sortOrder;

        $record->validate();
        $model->addErrors($record->getErrors());

        $beforeSaveEvent = new SaveEvent($model, $isNew);
        $this->trigger(self::EVENT_BEFORE_SAVE, $beforeSaveEvent);

        if ($beforeSaveEvent->isValid && !$model->hasErrors()) {
            $transaction = \Craft::$app->getDb()->beginTransaction();

            try {
                $record->save(false);

                self::$statusCache[$record->id] = $record;

                if (null !== $transaction) {
                    $transaction->commit();
                }

                $this->trigger(self::EVENT_AFTER_SAVE, new SaveEvent($model, $isNew));

                return true;
            } catch (\Exception $e) {
                if (null !== $transaction) {
                    $transaction->rollBack();
                }

                throw $e;
            }
        }

        return false;
    }

    public function deleteById(int $id): bool
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_SETTINGS_ACCESS);

        $model = $this->getStatusById($id);

        if (!$model) {
            return false;
        }

        $record = StatusRecord::findOne(['id' => $model->id]);
        if (!$record) {
            return false;
        }

        if ($this->getDefaultStatusId() == $id) {
            return false;
        }

        $beforeDeleteEvent = new DeleteEvent($model);
        $this->trigger(self::EVENT_BEFORE_DELETE, $beforeDeleteEvent);
        if (!$beforeDeleteEvent->isValid) {
            return false;
        }

        $transaction = \Craft::$app->getDb()->beginTransaction();

        try {
            $affectedRows = \Craft::$app
                ->getDb()
                ->createCommand()
                ->delete(StatusRecord::TABLE, ['id' => $record->id])
                ->execute()
            ;

            if (null !== $transaction) {
                $transaction->commit();
            }

            $this->trigger(self::EVENT_AFTER_DELETE, new DeleteEvent($model));

            return (bool) $affectedRows;
        } catch (\Exception $exception) {
            if (null !== $transaction) {
                $transaction->rollBack();
            }

            throw $exception;
        }
    }

    public function getNextSortOrder(): int
    {
        return (int) (new Query())
            ->select('MAX([[sortOrder]])')
            ->from(StatusRecord::TABLE)
            ->scalar()
        ;
    }

    private function createStatus(array $data): StatusModel
    {
        return new StatusModel($data);
    }

    private function getStatusQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'statuses.id',
                    'statuses.name',
                    'statuses.handle',
                    'statuses.color',
                    'statuses.sortOrder',
                ]
            )
            ->from(StatusRecord::TABLE.' statuses')
            ->orderBy(['statuses.sortOrder' => \SORT_ASC])
        ;
    }
}
