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

namespace Solspace\Freeform\Form;

use Carbon\Carbon;
use craft\db\Query;
use craft\elements\User;
use craft\helpers\Template;
use Solspace\Freeform\Bundles\Translations\TranslationProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Fields\TransformValueEvent;
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\CreateSubmissionEvent;
use Solspace\Freeform\Events\Forms\FormLoadedEvent;
use Solspace\Freeform\Events\Forms\GetCustomPropertyEvent;
use Solspace\Freeform\Events\Forms\HandleRequestEvent;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\PersistStateEvent;
use Solspace\Freeform\Events\Forms\QuickLoadEvent;
use Solspace\Freeform\Events\Forms\RegisterContextEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Forms\ResetEvent;
use Solspace\Freeform\Events\Forms\SetPropertiesEvent;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\CheckboxField;
use Solspace\Freeform\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Fields\Interfaces\PersistentValueInterface;
use Solspace\Freeform\Form\Bags\PropertyBag;
use Solspace\Freeform\Form\Layout\FormLayout;
use Solspace\Freeform\Form\Layout\Page;
use Solspace\Freeform\Form\Settings\Implementations\BehaviorSettings;
use Solspace\Freeform\Form\Settings\Settings;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Attributes\FormAttributesCollection;
use Solspace\Freeform\Library\Collections\FieldCollection;
use Solspace\Freeform\Library\Collections\PageCollection;
use Solspace\Freeform\Library\Collections\RowCollection;
use Solspace\Freeform\Library\Database\FormHandlerInterface;
use Solspace\Freeform\Library\Database\SubmissionHandlerInterface;
use Solspace\Freeform\Library\DataObjects\DisabledFunctionality;
use Solspace\Freeform\Library\DataObjects\FormActionInterface;
use Solspace\Freeform\Library\DataObjects\Relations;
use Solspace\Freeform\Library\FileUploads\FileUploadHandlerInterface;
use Solspace\Freeform\Library\FormTypes\FormTypeInterface;
use Solspace\Freeform\Library\Serialization\Normalizers\CustomNormalizerInterface;
use Solspace\Freeform\Records\Form\FormIntegrationRecord;
use Solspace\Freeform\Records\IntegrationRecord;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Annotation\Ignore;
use Twig\Markup;
use yii\base\Event;
use yii\web\Request;

abstract class Form implements FormTypeInterface, \IteratorAggregate, CustomNormalizerInterface, \JsonSerializable
{
    public const HASH_KEY = 'hash';
    public const ACTION_KEY = 'freeform-action';
    public const SUBMISSION_FLASH_KEY = 'freeform_submission_flash';

    public const EVENT_GRAPHQL_REQUEST = 'graphql-request';
    public const EVENT_FORM_LOADED = 'form-loaded';
    public const EVENT_ON_STORE_SUBMISSION = 'on-store-submission';
    public const EVENT_REGISTER_CONTEXT = 'register-context';
    public const EVENT_RENDER_BEFORE_OPEN_TAG = 'render-before-opening-tag';
    public const EVENT_RENDER_AFTER_OPEN_TAG = 'render-after-opening-tag';
    public const EVENT_RENDER_BEFORE_CLOSING_TAG = 'render-before-closing-tag';
    public const EVENT_RENDER_AFTER_CLOSING_TAG = 'render-after-closing-tag';
    public const EVENT_RENDER_CAPTCHAS = 'render-captchas';
    public const EVENT_COLLECT_SCRIPTS = 'collect-scripts';
    public const EVENT_OUTPUT_AS_JSON = 'output-as-json';
    public const EVENT_SET_PROPERTIES = 'set-properties';
    public const EVENT_SUBMIT = 'submit';
    public const EVENT_ON_SUBMIT_RESPONSE = 'on-submit-response';
    public const EVENT_AFTER_SUBMIT = 'after-submit';
    public const EVENT_BEFORE_VALIDATE = 'before-validate';
    public const EVENT_AFTER_VALIDATE = 'after-validate';
    public const EVENT_ATTACH_TAG_ATTRIBUTES = 'attach-tag-attributes';
    public const EVENT_BEFORE_HANDLE_REQUEST = 'before-handle-request';
    public const EVENT_AFTER_HANDLE_REQUEST = 'after-handle-request';
    public const EVENT_BEFORE_RESET = 'before-reset-form';
    public const EVENT_AFTER_RESET = 'after-reset-form';
    public const EVENT_PERSIST_STATE = 'persist-state';
    public const EVENT_GENERATE_RETURN_URL = 'generate-return-url';
    public const EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD = 'prepare-ajax-response-payload';
    public const EVENT_CREATE_SUBMISSION = 'create-submission';
    public const EVENT_SEND_NOTIFICATIONS = 'send-notifications';
    public const EVENT_GET_CUSTOM_PROPERTY = 'get-custom-property';
    public const EVENT_QUICK_LOAD = 'quick-load';
    public const EVENT_CONTEXT_RETRIEVAL = 'context-retrieval';

    public const PROPERTY_STORED_VALUES = 'storedValues';
    public const PROPERTY_PAGE_INDEX = 'pageIndex';
    public const PROPERTY_PAGE_HISTORY = 'pageHistory';
    public const PROPERTY_SPAM_REASONS = 'spamReasons';

    public const RETURN_URI_KEY = 'formReturnUrl';

    public const DATA_DISABLE = 'disable';
    public const DATA_RELATIONS = 'relations';

    protected FormLayout $layout;
    protected FormAttributesCollection $attributes;
    private PropertyBag $propertyBag;

    private ?int $id;
    private ?string $uid;

    // TODO: create a collection to handle error messages
    private array $errors = [];

    // TODO: create a collection to handle form actions
    /** @var FormActionInterface[] */
    private array $actions = [];

    private bool $finished = false;
    private bool $valid = false;
    private array|bool $disableFunctionality = false;
    private bool $disableAjaxReset = false;
    private bool $pagePosted = false;
    private bool $navigatingBack = false;
    private bool $formPosted = false;
    private bool $duplicate = false;
    private bool $graphqlPosted = false;
    private array $graphqlArguments = [];

    private Carbon $dateCreated;
    private Carbon $dateUpdated;

    private ?int $createdByUserId;
    private ?int $updatedByUserId;

    private ?Carbon $dateArchived;

    private ?Submission $submission = null;

    public function __construct(
        array $config,
        private Settings $settings,
        private PropertyAccessor $accessor,
        private TranslationProvider $translationProvider,
    ) {
        $this->id = $config['id'] ?? null;
        $this->uid = $config['uid'] ?? null;

        $this->dateCreated = new Carbon($config['dateCreated'] ?? 'now');
        $this->dateUpdated = new Carbon($config['dateUpdated'] ?? 'now');

        $this->createdByUserId = $config['createdByUserId'] ?? null;
        $this->updatedByUserId = $config['updatedByUserId'] ?? null;

        $this->dateArchived = $config['dateArchived'] ?? null ? new Carbon($config['dateArchived']) : null;

        $this->propertyBag = new PropertyBag($this);
        $this->attributes = new FormAttributesCollection();
        $this->attributes->merge($settings->getGeneral()->attributes);

        Event::trigger(self::class, self::EVENT_FORM_LOADED, new FormLoadedEvent($this));
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __get(string $name)
    {
        $generalSettings = $this->getSettings()->getGeneral();
        if ($this->accessor->isReadable($generalSettings, $name)) {
            return $this->accessor->getValue($generalSettings, $name);
        }

        $event = new GetCustomPropertyEvent($this, $name);
        Event::trigger(self::class, self::EVENT_GET_CUSTOM_PROPERTY, $event);

        if ($event->getIsSet()) {
            return $event->getValue();
        }
    }

    public function __isset(string $name): bool
    {
        $event = new GetCustomPropertyEvent($this, $name);
        Event::trigger(self::class, self::EVENT_GET_CUSTOM_PROPERTY, $event);

        return $event->getIsSet();
    }

    public function get(mixed $fieldIdentificator): ?FieldInterface
    {
        return $this->getLayout()->getField($fieldIdentificator);
    }

    public function hasFieldType(string $type): bool
    {
        return $this->getLayout()->getFields()->hasFieldType($type);
    }

    public function getProperties(): PropertyBag
    {
        return $this->propertyBag;
    }

    public function getAttributes(): FormAttributesCollection
    {
        return $this->attributes;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid ?? '';
    }

    public function getName(): string
    {
        return $this->translationProvider->getTranslation(
            $this,
            'general',
            'name',
            $this->getSettings()->getGeneral()->name,
        );
    }

    public function getHandle(): string
    {
        return $this->getSettings()->getGeneral()->handle;
    }

    public function getColor(): string
    {
        return $this->getSettings()->getGeneral()->color;
    }

    public function getHash(): string
    {
        return $this->getProperties()->get(self::HASH_KEY, '');
    }

    public function getDescription(): string
    {
        return $this->translationProvider->getTranslation(
            $this,
            'general',
            'description',
            $this->getSettings()->getGeneral()->description,
        );
    }

    public function getCurrentPage(): Page
    {
        return $this->getLayout()->getPages()->getByIndex($this->getCurrentPageIndex());
    }

    public function getNextPage(): Page
    {
        return $this->layout->getPages()->getByIndex(
            $this->propertyBag->get(self::PROPERTY_PAGE_INDEX, 0) + 1
        );
    }

    public function getCurrentPageIndex(): int
    {
        return $this->propertyBag->get(self::PROPERTY_PAGE_INDEX, 0);
    }

    public function getRows(): RowCollection
    {
        return $this->getCurrentPage()->getRows();
    }

    public function getFields(): FieldCollection
    {
        return $this->getCurrentPage()->getFields();
    }

    public function getReturnUrl(): string
    {
        return $this->getSettings()->getBehavior()->returnUrl;
    }

    public function getAnchor(): string
    {
        $hash = $this->getHash();
        $id = $this->getProperties()->get('id', $this->getId());
        $hashedId = substr(sha1($id.$this->getHandle()), 0, 6);

        return "{$hashedId}-form-{$hash}";
    }

    public function isAjaxEnabled(): bool
    {
        return $this->getSettings()->getBehavior()->ajax;
    }

    public function isIntegrationEnabled(string $integrationHandle): ?bool
    {
        static $integrationStateMap = [];

        if (!isset($integrationStateMap[$this->getId()])) {
            $integrationStateMap[$this->getId()] = (new Query())
                ->select('fi.[[enabled]]')
                ->from(FormIntegrationRecord::TABLE.' fi')
                ->innerJoin(IntegrationRecord::TABLE.' i', 'fi.[[integrationId]] = i.[[id]]')
                ->indexBy('i.handle')
                ->where(['fi.[[formId]]' => $this->getId()])
                ->column()
            ;
        }

        $result = $integrationStateMap[$this->getId()][$integrationHandle] ?? null;
        if (null !== $result) {
            return (bool) $result;
        }

        return null;
    }

    public function isCaptchaEnabled(): bool
    {
        if ($this->isDisabled()->captchas) {
            return false;
        }

        return true;
    }

    public function isMultiPage(): bool
    {
        return \count($this->getPages()) > 1;
    }

    public function getPages(): PageCollection
    {
        return $this->getLayout()->getPages();
    }

    public function getLayout(): FormLayout
    {
        if (!isset($this->layout)) {
            $this->layout = Freeform::getInstance()->formLayouts->getLayout($this);
        }

        return $this->layout;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $message): self
    {
        $this->errors[] = $message;

        return $this;
    }

    /**
     * @return FormActionInterface[]
     */
    public function getActions(): array
    {
        return $this->actions ?? [];
    }

    public function addAction(FormActionInterface $action): self
    {
        $this->actions[] = $action;

        return $this;
    }

    public function addErrors(array $messages): self
    {
        $this->errors = array_merge($this->errors, $messages);

        return $this;
    }

    public function isMarkedAsSpam(): bool
    {
        return !empty($this->getSpamReasons());
    }

    public function getSpamReasons(): array
    {
        return $this->getProperties()->get(self::PROPERTY_SPAM_REASONS, []);
    }

    public function disableAjaxReset(): self
    {
        $this->disableAjaxReset = true;

        return $this;
    }

    public function isAjaxResetDisabled(): bool
    {
        return $this->disableAjaxReset;
    }

    public function markAsSpam(string $type, string $message): self
    {
        $bag = $this->getProperties();

        $reasons = $this->getSpamReasons();

        foreach ($reasons as $reason) {
            if ($reason['type'] === $type && $reason['message'] === $message) {
                return $this;
            }
        }

        $reasons[] = ['type' => $type, 'message' => $message];

        $bag->set(self::PROPERTY_SPAM_REASONS, $reasons);

        return $this;
    }

    public function hasErrors(): bool
    {
        $errorCount = \count($this->getErrors());
        foreach ($this->getLayout()->getFields() as $field) {
            $errorCount += \count($field->getErrors());
        }

        return $errorCount > 0;
    }

    public function isSubmittedSuccessfully(): bool
    {
        return $this->getSubmissionHandler()->wasFormFlashSubmitted($this);
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function setFinished(bool $value): self
    {
        $this->finished = $value;

        return $this;
    }

    public function isPagePosted(): bool
    {
        return $this->pagePosted;
    }

    public function setPagePosted(bool $pagePosted): self
    {
        $this->pagePosted = $pagePosted;

        return $this;
    }

    public function isNavigatingBack(): bool
    {
        return $this->navigatingBack;
    }

    public function setNavigatingBack(bool $navigatingBack): self
    {
        $this->navigatingBack = $navigatingBack;

        return $this;
    }

    public function isFormPosted(): bool
    {
        return $this->formPosted;
    }

    public function setFormPosted(bool $formPosted): self
    {
        $this->formPosted = $formPosted;

        return $this;
    }

    public function setDuplicate(bool $duplicate): self
    {
        $this->duplicate = $duplicate;

        return $this;
    }

    public function isDuplicate(): bool
    {
        return $this->duplicate;
    }

    public function isGraphQLPosted(): bool
    {
        return $this->graphqlPosted;
    }

    public function getDateCreated(): Carbon
    {
        return $this->dateCreated;
    }

    public function getDateUpdated(): Carbon
    {
        return $this->dateUpdated;
    }

    public function getCreatedBy(): ?User
    {
        if (!$this->createdByUserId) {
            return null;
        }

        return User::findOne($this->createdByUserId);
    }

    public function getUpdatedBy(): ?User
    {
        if (!$this->updatedByUserId) {
            return null;
        }

        return User::findOne($this->updatedByUserId);
    }

    public function getDateArchived(): ?Carbon
    {
        return $this->dateArchived;
    }

    #[Ignore]
    public function getSubmission(): ?Submission
    {
        return $this->submission;
    }

    public function setSubmission(Submission $submission): self
    {
        $this->submission = $submission;

        return $this;
    }

    public function setGraphQLPosted(bool $graphqlPosted): self
    {
        $this->graphqlPosted = $graphqlPosted;

        $this->pagePosted = true;
        $this->formPosted = true;

        return $this;
    }

    public function getGraphQLArguments(): array
    {
        return $this->graphqlArguments;
    }

    public function setGraphQLArguments(array $graphqlArguments): self
    {
        $this->graphqlArguments = $graphqlArguments;

        return $this;
    }

    public function handleRequest(Request $request): bool
    {
        $this->createSubmission();

        $method = strtoupper($this->getProperties()->get('method', 'post'));
        if ($method !== $request->getMethod()) {
            return false;
        }

        $event = new HandleRequestEvent($this, $request);
        Event::trigger(self::class, self::EVENT_BEFORE_HANDLE_REQUEST, $event);

        if (!$event->isValid) {
            return false;
        }

        if ($this->isPagePosted()) {
            $this->validate();
        }

        $event = new HandleRequestEvent($this, $request);
        Event::trigger(self::class, self::EVENT_AFTER_HANDLE_REQUEST, $event);

        return $event->isValid;
    }

    public function quickLoad(array $payload): void
    {
        $this->getProperties()->merge($payload['properties'] ?? []);
        $this->getAttributes()->merge($payload['attributes'] ?? []);

        $this->createSubmission();

        Event::trigger(
            self::class,
            self::EVENT_QUICK_LOAD,
            new QuickLoadEvent($this, $payload)
        );
    }

    public function persistState(): void
    {
        Event::trigger(self::class, self::EVENT_PERSIST_STATE, new PersistStateEvent($this));
    }

    public function registerContext(?array $renderProperties = null): void
    {
        $this->setProperties($renderProperties);

        Event::trigger(self::class, self::EVENT_REGISTER_CONTEXT, new RegisterContextEvent($this));
    }

    public function render(?array $renderProperties = null): ?Markup
    {
        $this->setProperties($renderProperties);
        $formTemplate = $this->getProperties()->get(
            'formattingTemplate',
            $this->getSettings()->getGeneral()->formattingTemplate
        );

        $successBehavior = $this->getSettings()->getBehavior()->successBehavior;

        if (
            ($this->isSubmittedSuccessfully() || $this->isFinished())
            && BehaviorSettings::SUCCESS_BEHAVIOR_LOAD_SUCCESS_TEMPLATE === $successBehavior
        ) {
            return $this->getFormHandler()->renderSuccessTemplate($this);
        }

        return $this->getFormHandler()->renderFormTemplate($this, $formTemplate);
    }

    public function renderTag(?array $renderProperties = null): Markup
    {
        $this->registerContext($renderProperties);

        $output = '';

        $beforeTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_BEFORE_OPEN_TAG, $beforeTag);
        $output .= $beforeTag->getChunksAsString();

        $event = new AttachFormAttributesEvent($this);
        Event::trigger(self::class, self::EVENT_ATTACH_TAG_ATTRIBUTES, $event);

        $output .= '<form'.$this->getAttributes().$this->getAttributes()->getForm().'>'.\PHP_EOL;

        $afterTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_AFTER_OPEN_TAG, $afterTag);
        $output .= $afterTag->getChunksAsString();

        return Template::raw($output);
    }

    public function renderClosingTag(bool $generateTag = true): Markup
    {
        $output = '';

        $beforeTag = new RenderTagEvent($this, $generateTag);
        Event::trigger(self::class, self::EVENT_RENDER_BEFORE_CLOSING_TAG, $beforeTag);
        $output .= $beforeTag->getChunksAsString();

        $output .= $generateTag ? '</form>' : '';

        $afterTag = new RenderTagEvent($this, $generateTag);
        Event::trigger(self::class, self::EVENT_RENDER_AFTER_CLOSING_TAG, $afterTag);
        $output .= $afterTag->getChunksAsString();

        return Template::raw($output);
    }

    public function renderCaptchas(): Markup
    {
        $event = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_CAPTCHAS, $event);

        return Template::raw($event->getChunksAsString());
    }

    public function getFormHandler(): FormHandlerInterface
    {
        return Freeform::getInstance()->forms;
    }

    public function getSubmissionHandler(): SubmissionHandlerInterface
    {
        return Freeform::getInstance()->submissions;
    }

    public function getFileUploadHandler(): FileUploadHandlerInterface
    {
        return Freeform::getInstance()->files;
    }

    public function isDisabled(): DisabledFunctionality
    {
        $disableSettings = $this->disableFunctionality ?: $this->getProperties()->get(self::DATA_DISABLE);
        if ($this->isMarkedAsSpam()) {
            $disableSettings = true;
        }

        return new DisabledFunctionality($disableSettings);
    }

    public function disableFunctionality(null|array|bool $config = null): self
    {
        $this->disableFunctionality = $config ?? true;

        return $this;
    }

    public function getRelations(): Relations
    {
        return new Relations($this->getProperties()->get(self::DATA_RELATIONS));
    }

    public function setProperties(?array $properties = null): self
    {
        $event = new SetPropertiesEvent($this, $properties ?? []);
        Event::trigger(
            self::class,
            self::EVENT_SET_PROPERTIES,
            $event
        );

        $this->propertyBag->merge($event->getProperties());

        return $this;
    }

    // TODO: make the hash be a UID instead
    public function getOptInDataTargetField(): ?CheckboxField
    {
        $hash = $this->getSettings()->getGeneral()->optInCheckbox;
        if ($hash) {
            $field = $this->get($hash);

            if ($field instanceof CheckboxField) {
                return $field;
            }
        }

        return null;
    }

    /**
     * If the Opt-In has been selected, returns if it's checked or not
     * If it's disabled, then just returns true.
     */
    public function hasOptInPermission(): bool
    {
        $field = $this->getOptInDataTargetField();
        if ($field) {
            return (bool) $field->getValue();
        }

        return true;
    }

    public function hasFieldBeenSubmitted(AbstractField $field): bool
    {
        return isset($this->getProperties()->get(self::PROPERTY_STORED_VALUES, [])[$field->getHandle()]);
    }

    public function reset(): void
    {
        $event = new ResetEvent($this);
        Event::trigger(self::class, self::EVENT_BEFORE_RESET, $event);

        if (!$event->isValid) {
            return;
        }

        Event::trigger(self::class, self::EVENT_AFTER_RESET, $event);
    }

    public function getFieldPrefix(): ?string
    {
        return $this->getProperties()->get('fieldIdPrefix');
    }

    public function isLastPage(): bool
    {
        $currentPageIndex = $this->getProperties()->get(self::PROPERTY_PAGE_INDEX, 0);

        return $currentPageIndex === (\count($this->getPages()) - 1);
    }

    public function toArray(array $fields = [], array $expand = [], $recursive = true): array
    {
        return $this->jsonSerialize();
    }

    // ==========================
    // INTERFACE IMPLEMENTATIONS
    // ==========================
    public function getIterator(): \ArrayIterator
    {
        if (isset($this->layout)) {
            return $this->layout->getPages()->current()->getIterator();
        }

        // This prevents Twig from failing a `is not empty` check
        return new \ArrayIterator([true]);
    }

    public function jsonSerialize(): array
    {
        $settings = $this->getSettings();
        $isMultipart = $this->getLayout()->hasFields(FileUploadInterface::class);

        $object = [
            'id' => $this->getId(),
            'hash' => $this->getHash(),
            'name' => $this->getName(),
            'handle' => $this->getHandle(),
            'class' => static::class,
            'enctype' => $isMultipart ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
            'properties' => $this->getProperties(),
            'attributes' => $this->getAttributes(),
            'settings' => [
                'behavior' => $settings->getBehavior(),
                'general' => $settings->getGeneral(),
            ],
        ];

        $event = new OutputAsJsonEvent($this, $object);
        Event::trigger(self::class, self::EVENT_OUTPUT_AS_JSON, $event);

        return $event->getJsonObject();
    }

    public function normalize(): array
    {
        return $this->jsonSerialize();
    }

    /**
     * Legacy alias for older FF versions.
     *
     * @deprecated {@see getAttributes()} instead. Will be removed in FF v6.0
     */
    public function customAttributes(): array
    {
        \Craft::$app->deprecator->log(
            'freeform.form.customAttributes',
            'Freeform\'s `form.customAttributes` have been deprecated. Please use `form.attributes` and `form.properties` instead. Will be removed in Freeform 6.0',
        );

        $attributes = $this->getAttributes();

        return [
            'id' => $this->getId(),
            'class' => $attributes->getForm()->get('class'),
            'method' => $this->getProperties()->get('method', 'post'),
            'action' => $attributes->getForm()->get('action'),
            'status' => '',
            'returnUrl' => '',
            'rowClass' => $attributes->getRow()->get('class'),
            'columnClass' => '',
            'submitClass' => '',
            'inputClass' => '',
            'labelClass' => '',
            'errorClass' => '',
            'instructionsClass' => '',
            'instructionsBelowField' => true,
            'overrideValues' => [],
            'formAttributes' => $attributes->getForm(),
            'inputAttributes' => '',
            'useRequiredAttribute' => true,
        ];
    }

    public function getErrorMessage(): string
    {
        return $this->getSettings()->getBehavior()->getErrorMessage();
    }

    public function getSuccessMessage(): string
    {
        return $this->getSettings()->getBehavior()->getSuccessMessage();
    }

    public function valuesFromArray(array $values): void
    {
        foreach ($this->getLayout()->getFields() as $field) {
            if ($field instanceof PersistentValueInterface || !$field->getHandle()) {
                continue;
            }

            if (!isset($values[$field->getHandle()])) {
                continue;
            }

            $field->setValue($values[$field->getHandle()]);
        }
    }

    public function valuesFromSubmission(Submission $submission): void
    {
        $fields = $submission->getFieldCollection();

        foreach ($this->getLayout()->getFields() as $field) {
            if ($field instanceof PersistentValueInterface || !$field->getHandle()) {
                continue;
            }

            if (!$fields->has($field)) {
                continue;
            }

            $event = new TransformValueEvent($field, $fields->get($field)->getValue());
            Event::trigger(FieldInterface::class, FieldInterface::EVENT_TRANSFORM_FROM_POST, $event);

            if (!$event->isValid) {
                continue;
            }

            $field->setValue($event->getValue());
        }
    }

    private function createSubmission(): void
    {
        $submission = Submission::create($this);
        $event = new CreateSubmissionEvent($this, $submission);

        Event::trigger(
            self::class,
            self::EVENT_CREATE_SUBMISSION,
            $event,
        );

        $this->setSubmission($event->getSubmission());
    }

    private function validate(): void
    {
        $event = new ValidationEvent($this);
        Event::trigger(self::class, self::EVENT_BEFORE_VALIDATE, $event);

        if (!$event->isValid) {
            $this->valid = $event->getValidationOverride();

            return;
        }

        if ($this->isGraphQLPosted()) {
            $currentPageFields = [];
            foreach ($this->getLayout()->getFields() as $field) {
                if (!$field->includeInGqlSchema()) {
                    continue;
                }

                $currentPageFields[] = $field;
            }
        } else {
            $currentPageFields = $this->getCurrentPage()->getFields();
        }

        $isFormValid = true;
        foreach ($currentPageFields as $field) {
            $field->validate($this);
            if (!$field->isValid()) {
                $isFormValid = false;
            }
        }

        if ($this->hasErrors()) {
            $isFormValid = false;
        }

        $this->valid = $isFormValid;

        $event = new ValidationEvent($this);
        Event::trigger(self::class, self::EVENT_AFTER_VALIDATE, $event);

        if (!$event->isValid) {
            $this->valid = $event->getValidationOverride();
        }
    }
}
