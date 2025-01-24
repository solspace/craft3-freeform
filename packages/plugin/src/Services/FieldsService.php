<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Services;

use craft\commerce\elements\Product;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\Tag;
use craft\elements\User;
use Solspace\Commons\Helpers\PermissionHelper;
use Solspace\Freeform\Events\Fields\DeleteEvent;
use Solspace\Freeform\Events\Fields\FetchFieldTypes;
use Solspace\Freeform\Events\Fields\SaveEvent;
use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\AbstractField;
use Solspace\Freeform\Library\Composer\Components\Fields\DataContainers\Option;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\ExternalOptionsInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Configuration\ExternalOptionsConfiguration;
use Solspace\Freeform\Library\Database\FieldHandlerInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Factories\PredefinedOptionsFactory;
use Solspace\Freeform\Models\FieldModel;
use Solspace\Freeform\Records\FieldRecord;

class FieldsService extends BaseService implements FieldHandlerInterface
{
    public const EVENT_BEFORE_SAVE = 'beforeSave';
    public const EVENT_AFTER_SAVE = 'afterSave';
    public const EVENT_BEFORE_DELETE = 'beforeDelete';
    public const EVENT_AFTER_DELETE = 'afterDelete';
    public const EVENT_FETCH_TYPES = 'fetchTypes';
    public const EVENT_BEFORE_VALIDATE = 'beforeValidate';
    public const EVENT_AFTER_VALIDATE = 'afterValidate';

    /** @var FieldModel[] */
    private static $fieldCache;

    /** @var bool */
    private static $allFieldsLoaded;

    /** @var array */
    private static $fieldHandleCache;

    /** @var array */
    private static $optionsCache = [];

    /**
     * @param bool $indexById
     *
     * @return FieldModel[]
     */
    public function getAllFields($indexById = true): array
    {
        if (null === self::$fieldCache || !self::$allFieldsLoaded) {
            if (null === self::$fieldCache) {
                self::$fieldCache = [];
            }
            $fieldDisplayOrder = Freeform::getInstance()->settings->getFieldDisplayOrder();

            $orderBy = [];
            if (Freeform::FIELD_DISPLAY_ORDER_TYPE === $fieldDisplayOrder) {
                $orderBy['fields.type'] = \SORT_ASC;
            }
            $orderBy['fields.label'] = \SORT_ASC;

            $results = $this->getQuery()
                ->orderBy($orderBy)
                ->all()
            ;

            foreach ($results as $data) {
                $field = $this->createField($data);
                self::$fieldCache[$field->id] = $field;
            }

            self::$allFieldsLoaded = true;
        }

        if (!$indexById) {
            return array_values(self::$fieldCache);
        }

        return self::$fieldCache;
    }

    /**
     * @param bool $indexById
     */
    public function getAllFieldHandles($indexById = true): array
    {
        if (null === self::$fieldHandleCache) {
            $results = (new Query())
                ->select(['id', 'handle'])
                ->from(FieldRecord::TABLE)
                ->all()
            ;

            $list = [];
            foreach ($results as $result) {
                $list[$result['id']] = $result['handle'];
            }

            self::$fieldHandleCache = $list;
        }

        if (!$indexById) {
            return array_values(self::$fieldHandleCache);
        }

        return self::$fieldHandleCache;
    }

    public function getAllFieldIds(): array
    {
        return (new Query())
            ->select(['id'])
            ->from(FieldRecord::TABLE)
            ->column()
        ;
    }

    public function getFieldTypes(): array
    {
        $fetchTypesEvent = new FetchFieldTypes();
        $this->trigger(self::EVENT_FETCH_TYPES, $fetchTypesEvent);

        return $fetchTypesEvent->getTypes();
    }

    public function getEditableFieldTypes(): array
    {
        $fetchTypesEvent = new FetchFieldTypes();
        $this->trigger(self::EVENT_FETCH_TYPES, $fetchTypesEvent);

        return $fetchTypesEvent->getEditableTypes();
    }

    /**
     * @param int $id
     *
     * @return null|FieldModel
     */
    public function getFieldById($id)
    {
        if (null === self::$fieldCache) {
            self::$fieldCache = [];
        }

        if (null === self::$fieldCache || !isset(self::$fieldCache[$id])) {
            $result = $this->getQuery()
                ->where(['fields.id' => $id])
                ->one()
            ;

            if ($result) {
                $field = $this->createField($result);

                self::$fieldCache[$id] = $field;
            } else {
                self::$fieldCache[$id] = null;
            }
        }

        return self::$fieldCache[$id];
    }

    /**
     * @throws \Exception
     */
    public function save(FieldModel $model): bool
    {
        $isNew = !$model->id;

        if (!$isNew) {
            $record = FieldRecord::findOne(['id' => $model->id]);
        } else {
            $record = FieldRecord::create();
        }

        $record->type = $model->type;
        $record->handle = $model->handle;
        $record->label = $model->label;
        $record->required = $model->required;
        $record->instructions = $model->instructions;
        $record->metaProperties = $model->metaProperties;

        $record->validate();
        $model->addErrors($record->getErrors());

        $beforeSaveEvent = new SaveEvent($model, $isNew);
        $this->trigger(self::EVENT_BEFORE_SAVE, $beforeSaveEvent);

        if ($beforeSaveEvent->isValid && !$model->hasErrors()) {
            $transaction = \Craft::$app->getDb()->beginTransaction();

            try {
                $record->save(false);

                if ($isNew) {
                    $model->id = $record->id;
                }

                self::$fieldCache[$model->id] = $model;

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

    /**
     * @param int $fieldId
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteById($fieldId)
    {
        PermissionHelper::requirePermission(Freeform::PERMISSION_FIELDS_MANAGE);

        $model = $this->getFieldById($fieldId);

        if (!$model) {
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
                ->delete(FieldRecord::TABLE, ['id' => $model->id])
                ->execute();

            $this->deleteFieldFromForms($model);

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

    public function beforeValidate(AbstractField $field, Form $form)
    {
        $this->trigger(self::EVENT_BEFORE_VALIDATE, new ValidateEvent($field, $form));
    }

    public function afterValidate(AbstractField $field, Form $form)
    {
        $this->trigger(self::EVENT_AFTER_VALIDATE, new ValidateEvent($field, $form));
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsFromSource(string $source, $target, array $configuration = [], $selectedValues = []): array
    {
        $hash = sha1(json_encode([$source, $target, $configuration, $selectedValues]));

        if (!isset(self::$optionsCache[$hash])) {
            $config = new ExternalOptionsConfiguration($configuration);
            $labelField = $config->getLabelField() ?? 'title';
            $valueField = $config->getValueField() ?? 'id';
            $siteId = $config->getSiteId() ?? \Craft::$app->sites->currentSite->id;
            $options = [];

            if (!\is_array($selectedValues)) {
                $selectedValues = [$selectedValues];
            }

            switch ($source) {
                case ExternalOptionsInterface::SOURCE_ENTRIES:
                    $query = Entry::find()->sectionId($target)->siteId($siteId);

                    break;

                case ExternalOptionsInterface::SOURCE_CATEGORIES:
                    $query = Category::find()->groupId($target)->siteId($siteId);

                    break;

                case ExternalOptionsInterface::SOURCE_TAGS:
                    $query = Tag::find()->groupId($target)->siteId($siteId);

                    break;

                case ExternalOptionsInterface::SOURCE_USERS:
                    $query = User::find()
                        ->status(User::STATUS_ACTIVE)
                        ->groupId($target)
                        ->siteId($siteId)
                    ;

                    break;

                case ExternalOptionsInterface::SOURCE_ASSETS:
                    $query = Asset::find()->volumeId($target)->siteId($siteId);

                    break;

                case ExternalOptionsInterface::SOURCE_COMMERCE_PRODUCTS:
                    if (!class_exists('craft\commerce\elements\Product')) {
                        return [];
                    }

                    $query = Product::find()->typeId($target)->siteId($siteId);

                    break;

                case ExternalOptionsInterface::SOURCE_PREDEFINED:
                    return PredefinedOptionsFactory::create($target, $config, $selectedValues);
            }

            $orderBy = $config->getOrderBy() ?? 'id';
            $sort = 'desc' === strtolower($config->getSort()) ? \SORT_DESC : \SORT_ASC;
            $query->orderBy([$orderBy => $sort]);

            $items = $query->all();

            foreach ($items as $item) {
                switch ($source) {
                    case ExternalOptionsInterface::SOURCE_ASSETS:
                        $defaultLabel = $item->getFilename();

                        break;

                    case ExternalOptionsInterface::SOURCE_USERS:
                        $defaultLabel = $item->username;

                        break;

                    default:
                        $defaultLabel = $item->title;

                        break;
                }

                if (ExternalOptionsInterface::SOURCE_COMMERCE_PRODUCTS === $source) {
                    try {
                        $label = $item->getDefaultVariant()->getFieldValue($labelField);
                    } catch (\yii\base\Exception $exception) {
                        $label = $item->{$labelField} ?? $defaultLabel;
                    }

                    try {
                        $value = $item->getDefaultVariant()->getFieldValue($valueField);
                    } catch (\yii\base\Exception $exception) {
                        $value = $item->{$valueField} ?? $item->id;
                    }
                } else {
                    $label = $item->{$labelField} ?? $defaultLabel;
                    $value = $item->{$valueField} ?? $item->id;
                }

                $options[] = new Option($label, $value, \in_array($value, $selectedValues, false));
            }

            if ($config->getEmptyOption()) {
                array_unshift(
                    $options,
                    new Option($config->getEmptyOption(), '', \in_array('', $selectedValues, true))
                );
            }

            self::$optionsCache[$hash] = $options;
        }

        return self::$optionsCache[$hash];
    }

    /**
     * @throws \Exception
     */
    private function deleteFieldFromForms(FieldModel $model)
    {
        $forms = $this->getFormsService()->getAllForms();

        foreach ($forms as $form) {
            try {
                $composer = $form->getComposer();
                $composer->removeFieldById($model->id);
                $form->layoutJson = $composer->getComposerStateJSON();
                $this->getFormsService()->save($form);
            } catch (FreeformException $e) {
            }
        }
    }

    private function getQuery(): Query
    {
        return (new Query())
            ->select(
                [
                    'fields.id',
                    'fields.type',
                    'fields.label',
                    'fields.handle',
                    'fields.required',
                    'fields.instructions',
                    'fields.metaProperties',
                ]
            )
            ->from(FieldRecord::TABLE.' fields')
            ->orderBy(['fields.label' => \SORT_ASC])
        ;
    }

    private function createField(array $data): FieldModel
    {
        $field = new FieldModel($data);

        if (\is_string($field->metaProperties) && '' !== $field->metaProperties) {
            $field->metaProperties = json_decode($field->metaProperties, true);
        }

        return $field;
    }
}
