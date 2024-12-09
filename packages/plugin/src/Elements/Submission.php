<?php

namespace Solspace\Freeform\Elements;

use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\elements\actions\Restore;
use craft\elements\Asset;
use craft\elements\User;
use craft\events\RegisterElementActionsEvent;
use craft\helpers\Cp as CraftCp;
use craft\helpers\Db;
use craft\helpers\Html;
use craft\helpers\StringHelper as CraftStringHelper;
use craft\helpers\UrlHelper;
use Solspace\Freeform\Bundles\GraphQL\GqlPermissions;
use Solspace\Freeform\Elements\Actions\DeleteAllSubmissionsAction;
use Solspace\Freeform\Elements\Actions\DeleteSubmissionAction;
use Solspace\Freeform\Elements\Actions\ExportCSVAction;
use Solspace\Freeform\Elements\Actions\Pro\ResendNotificationsAction;
use Solspace\Freeform\Elements\Actions\SendNotificationAction;
use Solspace\Freeform\Elements\Actions\SetSubmissionStatusAction;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Events\Fields\TransformValueEvent;
use Solspace\Freeform\Events\Submissions\ProcessFieldValueEvent;
use Solspace\Freeform\Events\Submissions\RenderTableValueEvent;
use Solspace\Freeform\Events\Submissions\SetSubmissionFieldValueEvent;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Collections\FieldCollection;
use Solspace\Freeform\Library\DataObjects\SpamReason;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\Helpers\ColorHelper;
use Solspace\Freeform\Library\Helpers\Cp;
use Solspace\Freeform\Library\Helpers\CryptoHelper;
use Solspace\Freeform\Library\Helpers\HashHelper;
use Solspace\Freeform\Library\Helpers\PermissionHelper;
use Solspace\Freeform\Library\Helpers\SearchHelper;
use Solspace\Freeform\Library\Helpers\SitesHelper;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Models\StatusModel;
use Solspace\Freeform\Records\FormRecord;
use Solspace\Freeform\Records\SpamReasonRecord;
use Solspace\Freeform\Services\NotesService;
use yii\base\Event;

class Submission extends Element
{
    public const TABLE_STD = 'freeform_submissions';
    public const TABLE = '{{%freeform_submissions}}';
    public const FIELD_COLUMN_PREFIX = 'field_';

    public const EVENT_PROCESS_SUBMISSION = 'process-submission';
    public const EVENT_PROCESS_FIELD_VALUE = 'process-field-value';
    public const EVENT_RENDER_TABLE_VALUE = 'render-submission-table-field';
    public const EVENT_SET_FIELD_VALUE = 'set-field-value';

    public const OPT_IN_DATA_TOKEN_LENGTH = 100;

    public ?int $formId = null;
    public ?int $userId = null;
    public ?int $statusId = null;
    public ?int $incrementalId = null;
    public ?string $token = null;
    public bool $isSpam = false;
    public bool $isHidden = false;
    public ?string $requestId = null;
    public ?string $ip = null;

    private ?FieldCollection $fieldCollection = null;
    private static array $permissionCache = [];
    private static array $deletableTokens = [];
    private array $indexedAttributeCache = [];

    /** @var SpamReason[] */
    private ?array $spamReasons = null;

    public function __construct($config = [])
    {
        $this->formId = $config['formId'] ?? null;

        parent::__construct($config);
    }

    public function __get($name): mixed
    {
        if (empty($name)) {
            return null;
        }

        $gettingByFieldMarker = false;
        if (preg_match('/^field:(\d+)$/', $name, $matches)) {
            $gettingByFieldMarker = true;
            $name = (int) $matches[1];
        }

        if (method_exists($this, 'get'.$name)) {
            return $this->{'get'.$name}();
        }

        try {
            return $this->getFieldCollection()->get($name);
        } catch (\Exception) {
            if ($gettingByFieldMarker) {
                return null;
            }
        }

        return parent::__get($name);
    }

    public function __set($name, $value)
    {
        if (preg_match('/^form_(\d+)__([a-z\d\-_]+)_(\d+)$/i', $name, $matches)) {
            $formId = (int) $matches[1];
            $id = (int) $matches[3];

            if ($formId !== (int) $this->formId) {
                return;
            }

            try {
                $field = $this->getFieldCollection()->get($id);

                if ($field instanceof MultiValueInterface && \is_string($value)) {
                    $value = json_decode($value, true);
                }

                $event = new SetSubmissionFieldValueEvent($field, $this, $value);
                $this->trigger(self::EVENT_SET_FIELD_VALUE, $event);

                $field->setValue($event->getValue());

                return;
            } catch (FreeformException) {
            }
        }

        parent::__set($name, $value);
    }

    public function __isset($name): bool
    {
        if ($this->getFieldCollection()->has($name)) {
            return true;
        }

        return parent::__isset($name);
    }

    public static function find(): SubmissionQuery
    {
        return (new SubmissionQuery(self::class))->isSpam(false);
    }

    public static function gqlTypeNameByContext(mixed $context): string
    {
        return $context->handle.'_Submission';
    }

    public static function gqlScopesByContext(mixed $context): array
    {
        return [GqlPermissions::CATEGORY_SUBMISSIONS.'.'.$context->uid];
    }

    public static function gqlMutationNameByContext(mixed $context): string
    {
        return 'save_'.self::gqlTypeNameByContext($context);
    }

    public static function displayName(): string
    {
        return Freeform::t('Submission');
    }

    public static function lowerDisplayName(): string
    {
        return Freeform::t('submission');
    }

    public static function pluralDisplayName(): string
    {
        return Freeform::t('Submissions');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Freeform::t('submissions');
    }

    public static function refHandle(): ?string
    {
        return 'submission';
    }

    public static function hasContent(): bool
    {
        return true;
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function isLocalized(): bool
    {
        return SitesHelper::isEnabled();
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    public static function statuses(): array
    {
        $statuses = [];

        foreach (Freeform::getInstance()->statuses->getAllStatuses() as $status) {
            $statuses[$status->handle] = [
                'label' => $status->name,
                'color' => ColorHelper::getCraftColor($status->color),
            ];
        }

        return $statuses;
    }

    public static function create(Form|FormRecord|int $form): self
    {
        if ($form instanceof Form) {
            $formId = $form->getId();
        } elseif ($form instanceof FormRecord) {
            $formId = $form->id;
        } else {
            $formId = (int) $form;
        }

        $submission = new static(['formId' => $formId]);
        $submission->generateToken();
        $submission->userId = \Craft::$app->user->getId() ?: null;

        return $submission;
    }

    public static function getContentTableName(Form $form): string
    {
        return self::generateContentTableName($form->getId(), $form->getHandle());
    }

    public static function generateContentTableName(int $id, string $handle): string
    {
        $prefix = \Craft::$app->db->tablePrefix;
        $prefixLength = \strlen($prefix);

        $maxHandleSize = 36 - $prefixLength;

        $handle = CraftStringHelper::toSnakeCase($handle);
        $handle = CraftStringHelper::truncate($handle, $maxHandleSize, '');
        $handle = trim($handle, '-_');

        return "{{%freeform_submissions_{$handle}_{$id}}}";
    }

    public static function generateFieldColumnName(int $id, string $handle): string
    {
        if (empty($handle)) {
            $handle = HashHelper::sha1($id, 10);
        }

        $handle = CraftStringHelper::toKebabCase($handle, '_');
        $handle = CraftStringHelper::truncate($handle, 50, '');
        $handle = trim($handle, '-_');

        return $handle.'_'.$id;
    }

    public static function getFieldColumnName(FieldInterface $field): string
    {
        return self::generateFieldColumnName($field->getId(), $field->getHandle());
    }

    /**
     * @return SpamReason[]
     */
    public function getSpamReasons(): array
    {
        if (null === $this->spamReasons) {
            $data = (new Query())
                ->select(['reasonType', 'reasonMessage'])
                ->from(SpamReasonRecord::TABLE)
                ->where(['submissionId' => $this->id])
                ->all()
            ;

            $reasons = [];
            foreach ($data as $item) {
                $reasons[] = new SpamReason($item['reasonType'], $item['reasonMessage']);
            }

            $this->spamReasons = $reasons;
        }

        return $this->spamReasons;
    }

    public function addIndexedAttribute(string $attribute): void
    {
        $this->indexedAttributeCache[$attribute] = true;
    }

    public function hasIndexedAttribute(string $attribute): bool
    {
        return \array_key_exists($attribute, $this->indexedAttributeCache);
    }

    public function getAuthor(): ?User
    {
        return $this->getUser();
    }

    public function getUser(): ?User
    {
        return $this->userId ? \Craft::$app->users->getUserById($this->userId) : null;
    }

    public function getStatus(): string
    {
        return $this->getStatusModel()->handle;
    }

    public function getStatusModel(): StatusModel
    {
        return Freeform::getInstance()->statuses->getStatusById($this->statusId);
    }

    public function getSubmissionDate(): \DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @return null|Asset[]
     */
    public function getAssets(string $fieldHandle): ?array
    {
        $field = $this->{$fieldHandle};
        $value = $field->getValue();

        if (!\is_array($value)) {
            $value = [$value];
        }

        $assets = [];
        foreach ($value as $assetId) {
            if ((int) $assetId > 0) {
                $assets[] = \Craft::$app->assets->getAssetById((int) $assetId);
            }
        }

        return $assets;
    }

    public function getFormFieldValue(FieldInterface $field): mixed
    {
        return $this->fieldCollection->get($field)?->getValue();
    }

    public function getFormFieldValues(): array
    {
        $values = [];
        foreach ($this as $field) {
            if (!$field->getHandle()) {
                continue;
            }

            $values[$field->getHandle()] = $field->getValue();
        }

        return $values;
    }

    public function setFormFieldValues(array $values, bool $override = true): self
    {
        foreach ($this as $field) {
            $value = null;
            if (isset($values[$field->getHandle()])) {
                $value = $values[$field->getHandle()];
            }

            if (!$override && null === $value) {
                continue;
            }

            $field->setValue($value);
        }

        return $this;
    }

    public function getForm(): ?Form
    {
        if (!$this->formId) {
            return null;
        }

        $formService = Freeform::getInstance()->forms;

        return $formService->getFormById((int) $this->formId);
    }

    public function getIsEditable(): bool
    {
        if (!isset(self::$permissionCache[$this->formId])) {
            if (PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE)) {
                self::$permissionCache[$this->formId] = true;
            } else {
                self::$permissionCache[$this->formId] = PermissionHelper::checkPermission(
                    PermissionHelper::prepareNestedPermission(
                        Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                        $this->formId
                    )
                );
            }
        }

        return self::$permissionCache[$this->formId];
    }

    public function canView(User $user): bool
    {
        return true;
    }

    public function getCpEditUrl(): ?string
    {
        return $this->getIsEditable() ? UrlHelper::cpUrl('freeform/submissions/'.$this->id) : false;
    }

    public function getEditorHtml(): string
    {
        $html = \Craft::$app->getView()
            ->renderTemplateMacro(
                '_includes/forms',
                'textField',
                [
                    [
                        'label' => Freeform::t('Title'),
                        'siteId' => $this->siteId,
                        'id' => 'title',
                        'name' => 'title',
                        'value' => $this->title,
                        'errors' => $this->getErrors('title'),
                        'first' => true,
                        'autofocus' => true,
                        'required' => true,
                    ],
                ]
            )
        ;

        $html .= parent::getEditorHtml();

        return $html;
    }

    public function afterSave(bool $isNew): void
    {
        $insertData = [
            'userId' => $this->userId ?: null,
            'formId' => $this->formId,
            'statusId' => $this->statusId,
            'incrementalId' => $this->incrementalId ?? $this->getNewIncrementalId(),
            'token' => $this->token,
            'ip' => $this->ip,
            'isSpam' => $this->isSpam,
            'isHidden' => $this->isHidden,
            'requestId' => $this->requestId,
        ];

        $contentData = [];
        foreach ($this as $field) {
            if ($field instanceof NoStorageInterface) {
                continue;
            }

            $event = new TransformValueEvent($field, $field->getValue());
            Event::trigger(FieldInterface::class, FieldInterface::EVENT_TRANSFORM_FOR_STORAGE, $event);

            $value = $event->getValue();

            if (\is_array($value)) {
                $value = json_encode($value);
            }

            if (\PHP_VERSION_ID >= 50400 && null !== $value) {
                $value = CraftStringHelper::emojiToShortcodes($value);
            }

            $event = new ProcessFieldValueEvent($field, $value);
            Event::trigger(self::class, self::EVENT_PROCESS_FIELD_VALUE, $event);

            $contentData[self::getFieldColumnName($field)] = $event->getValue();
        }

        $contentTable = self::getContentTableName($this->getForm());
        if ($isNew) {
            $contentData['id'] = $this->id;
            $insertData['id'] = $this->id;
            $insertData['dateCreated'] = Db::prepareDateForDb($this->dateCreated);
            $insertData['dateUpdated'] = Db::prepareDateForDb($this->dateUpdated);

            \Craft::$app->db->createCommand()
                ->insert(self::TABLE, $insertData)
                ->execute()
            ;

            \Craft::$app->db->createCommand()
                ->insert($contentTable, $contentData)
                ->execute()
            ;
        } else {
            \Craft::$app->db->createCommand()
                ->update(self::TABLE, $insertData, ['id' => $this->id])
                ->execute()
            ;

            \Craft::$app->db->createCommand()
                ->update($contentTable, $contentData, ['id' => $this->id])
                ->execute()
            ;

            $notesService = $this->getNotesService();
            $notesService->saveNote($this->id);
        }

        parent::afterSave($isNew);
    }

    public function enableDeletingByToken()
    {
        self::$deletableTokens[] = $this->token;
    }

    public function beforeDelete(): bool
    {
        if (\in_array($this->token, self::$deletableTokens, true)) {
            return true;
        }

        $canModifyAll = PermissionHelper::checkPermission(Freeform::PERMISSION_SUBMISSIONS_MANAGE);
        if ($canModifyAll) {
            return true;
        }

        return PermissionHelper::checkPermission(
            PermissionHelper::prepareNestedPermission(
                Freeform::PERMISSION_SUBMISSIONS_MANAGE,
                $this->formId
            )
        );
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        $fields = parent::toArray($fields, $expand, $recursive);

        foreach ($this as $field) {
            $handle = $field->getHandle();
            $fields[$handle] = $this->{$handle};
        }

        return $fields;
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->getFieldCollection()->getIterator();
    }

    public static function sortOptions(): array
    {
        return array_merge(
            ['title' => Freeform::t('Submission')],
            parent::sortOptions()
        );
    }

    public function getCurrentRevision(): ?ElementInterface
    {
        return null;
    }

    public static function actions(string $source): array
    {
        $actions = static::defineActions($source);

        // Give plugins a chance to modify them
        $event = new RegisterElementActionsEvent([
            'source' => $source,
            'actions' => $actions,
        ]);

        Event::trigger(static::class, self::EVENT_REGISTER_ACTIONS, $event);

        return $event->actions;
    }

    public function getFieldCollection(): FieldCollection
    {
        if (null === $this->fieldCollection && $this->getForm()) {
            $this->fieldCollection = $this->getForm()->getLayout()->getFields()->cloneCollection();
        }

        return $this->fieldCollection;
    }

    protected static function defineSources(?string $context = null): array
    {
        static $sources;

        if (null === $sources) {
            $site = SitesHelper::getCurrentCpSite();

            $formsService = Freeform::getInstance()->forms;
            $forms = $formsService->getAllForms(sites: $site?->handle);

            $allowedFormIds = Freeform::getInstance()->submissions->getAllowedReadFormIds();

            $items = [
                [
                    'key' => '*',
                    'label' => Freeform::t('All Submissions'),
                    'criteria' => ['formId' => $allowedFormIds],
                ],
                ['heading' => Freeform::t('Forms')],
            ];

            foreach ($forms as $form) {
                if (!\in_array($form->getId(), $allowedFormIds)) {
                    continue;
                }

                $items[] = [
                    'key' => 'form:'.$form->getId(),
                    'label' => $form->getName(),
                    'data' => [
                        'handle' => $form->getHandle(),
                    ],
                    'criteria' => [
                        'formId' => $form->getId(),
                    ],
                ];
            }

            $sources = $items;
        }

        return $sources;
    }

    protected static function defineTableAttributes(): array
    {
        static $attributes;

        if (null === $attributes) {
            $titles = [
                'userId' => ['label' => \Craft::t('app', 'Author')],
                'status' => ['label' => \Craft::t('app', 'Status')],
                'form' => ['label' => Freeform::t('Form')],
                'dateCreated' => ['label' => \Craft::t('app', 'Date Created')],
                'id' => ['label' => \Craft::t('app', 'ID')],
                'incrementalId' => ['label' => Freeform::t('Freeform ID')],
                'ip' => ['label' => Freeform::t('IP Address')],
                'spamReasons' => ['label' => Freeform::t('Spam Reasons')],
            ];

            // Hide Author from Craft Personal/Client
            if (\Craft::$app->getEdition() < \Craft::Pro) {
                unset($titles['userId']);
            }

            $attributes = $titles;
        }

        return $attributes;
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return [
            'id',
            'title',
            'status',
            'dateCreated',
            'form',
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        $searchable = parent::defineSearchableAttributes();

        $forms = Freeform::getInstance()->forms->getAllForms();

        foreach ($forms as $form) {
            $layout = $form->getLayout();

            /** @var FieldInterface $field */
            foreach ($layout->getFields() as $field) {
                $searchable[] = SearchHelper::maybeTruncateHandle($field->getHandle());
            }
        }

        return array_unique($searchable);
    }

    protected static function defineActions(?string $source = null): array
    {
        if ('*' === $source) {
            $message = Freeform::t('Are you sure you want to delete all submissions?');
        } else {
            $message = Freeform::t('Are you sure you want to delete all submissions for this form?');
        }

        $actions = [
            \Craft::$app->elements->createAction(
                [
                    'type' => DeleteSubmissionAction::class,
                    'confirmationMessage' => Freeform::t('Are you sure you want to delete the selected submissions?'),
                    'successMessage' => Freeform::t('Submissions deleted.'),
                ]
            ),
            \Craft::$app->elements->createAction(
                [
                    'type' => DeleteAllSubmissionsAction::class,
                    'confirmationMessage' => $message,
                    'successMessage' => Freeform::t('Submissions deleted.'),
                ]
            ),
            \Craft::$app->elements->createAction(['type' => SetSubmissionStatusAction::class]),
        ];

        if (PermissionHelper::checkPermission(Freeform::PERMISSION_ACCESS_QUICK_EXPORT)) {
            $actions[] = \Craft::$app->elements->createAction(['type' => ExportCSVAction::class]);
        }

        if (Freeform::getInstance()->isPro()) {
            $actions[] = \Craft::$app->elements->createAction(['type' => ResendNotificationsAction::class]);
            $actions[] = \Craft::$app->elements->createAction(['type' => SendNotificationAction::class]);
        }

        if (version_compare(\Craft::$app->getVersion(), '3.1', '>=')) {
            $actions[] = \Craft::$app->elements->createAction([
                'type' => Restore::class,
                'successMessage' => \Craft::t('app', 'Submissions restored.'),
                'partialSuccessMessage' => \Craft::t('app', 'Some submissions restored.'),
                'failMessage' => \Craft::t('app', 'Submissions not restored.'),
            ]);
        }

        return $actions;
    }

    protected function searchKeywords(string $attribute): string
    {
        // If this was likely truncated, find all the keywords that would end up here
        if (25 === CraftStringHelper::length($attribute)) {
            // In case multiple fields truncate to same prefix, concatenate all of that
            $return = [];

            foreach ($this->getFieldCollection() as $field) {
                $handle = $field->getHandle();
                if (CraftStringHelper::length($handle) >= 25 && CraftStringHelper::startsWith($handle, $attribute)) {
                    $return[] = $field->getValueAsString();
                }
            }

            return implode(' ', $return);
        }

        if ($field = $this->getFieldCollection()->get($attribute)) {
            return $field->getValueAsString();
        }

        return parent::searchKeywords($attribute);
    }

    // Craft 4
    protected function tableAttributeHtml(string $attribute): string
    {
        if ('status' === $attribute) {
            return $this->getStatusModel()->name;
        }

        if ('userId' === $attribute) {
            $user = $this->getAuthor();

            return $user ? \Craft::$app->view->renderTemplate('_elements/element', ['element' => $user]) : '';
        }

        if ('spamReasons' === $attribute) {
            $spamReasons = $this->getSpamReasons();
            if (empty($spamReasons)) {
                return '';
            }

            $reasons = [];
            foreach ($spamReasons as $reason) {
                $reasons[] = $reason->getType();
            }

            $translatedReasons = [];
            $reasons = array_unique($reasons);
            $reasons = array_filter($reasons);
            foreach ($reasons as $reason) {
                $translatedReasons[] = Freeform::t($reason);
            }

            return StringHelper::implodeRecursively(', ', $translatedReasons);
        }

        $value = $this->{$attribute};

        if (\is_array($value)) {
            return Html::decode(StringHelper::implodeRecursively(', ', $value));
        }

        if ($value instanceof FieldInterface) {
            $event = new RenderTableValueEvent($value, $this);
            $this->trigger(self::EVENT_RENDER_TABLE_VALUE, $event);

            if ($event->getOutput()) {
                return $event->getOutput();
            }

            return Html::encode($value);
        }

        return parent::tableAttributeHtml($attribute);
    }

    // Craft 5
    protected function attributeHtml(string $attribute): string
    {
        if ('status' === $attribute) {
            return Cp::componentStatusLabelHtml($this);
        }

        if ('userId' === $attribute) {
            $user = $this->getAuthor();

            return $user ? CraftCp::elementHtml($user) : '';
        }

        if ('spamReasons' === $attribute) {
            $spamReasons = $this->getSpamReasons();
            if (empty($spamReasons)) {
                return '';
            }

            $reasons = [];
            foreach ($spamReasons as $reason) {
                $reasons[] = $reason->getType();
            }

            $translatedReasons = [];
            $reasons = array_unique($reasons);
            $reasons = array_filter($reasons);
            foreach ($reasons as $reason) {
                $translatedReasons[] = Freeform::t($reason);
            }

            return StringHelper::implodeRecursively(', ', $translatedReasons);
        }

        $value = $this->{$attribute};

        if (\is_array($value)) {
            return Html::decode(StringHelper::implodeRecursively(', ', $value));
        }

        if ($value instanceof FieldInterface) {
            $event = new RenderTableValueEvent($value, $this);
            $this->trigger(self::EVENT_RENDER_TABLE_VALUE, $event);

            if ($event->getOutput()) {
                return $event->getOutput();
            }

            return Html::encode($value);
        }

        return parent::attributeHtml($attribute);
    }

    protected function getNotesService(): NotesService
    {
        return Freeform::getInstance()->notes;
    }

    private function generateToken(): void
    {
        $this->token = CryptoHelper::getUniqueToken(self::OPT_IN_DATA_TOKEN_LENGTH);
    }

    private function getNewIncrementalId(): int
    {
        $maxIncrementalId = (int) (new Query())
            ->select(['MAX([[incrementalId]])'])
            ->from(self::TABLE)
            ->scalar()
        ;

        return ++$maxIncrementalId;
    }
}
