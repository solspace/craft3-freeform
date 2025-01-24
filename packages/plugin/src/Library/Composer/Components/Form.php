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

namespace Solspace\Freeform\Library\Composer\Components;

use craft\helpers\Template;
use Psr\Log\LoggerInterface;
use Solspace\Freeform\Bundles\Form\Context\Request\EditSubmissionContext;
use Solspace\Freeform\Bundles\Form\PayloadForwarding\PayloadForwarding;
use Solspace\Freeform\Events\Forms\AttachFormAttributesEvent;
use Solspace\Freeform\Events\Forms\FormLoadedEvent;
use Solspace\Freeform\Events\Forms\GetCustomPropertyEvent;
use Solspace\Freeform\Events\Forms\HandleRequestEvent;
use Solspace\Freeform\Events\Forms\HydrateEvent;
use Solspace\Freeform\Events\Forms\OutputAsJsonEvent;
use Solspace\Freeform\Events\Forms\PersistStateEvent;
use Solspace\Freeform\Events\Forms\RegisterContextEvent;
use Solspace\Freeform\Events\Forms\RenderTagEvent;
use Solspace\Freeform\Events\Forms\ResetEvent;
use Solspace\Freeform\Events\Forms\SetPropertiesEvent;
use Solspace\Freeform\Events\Forms\UpdateAttributesEvent;
use Solspace\Freeform\Events\Forms\ValidationEvent;
use Solspace\Freeform\Fields\CheckboxField;
use Solspace\Freeform\Fields\HiddenField;
use Solspace\Freeform\Fields\MailingListField;
use Solspace\Freeform\Form\Bags\AttributeBag;
use Solspace\Freeform\Form\Bags\PropertyBag;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Attributes\CustomFormAttributes;
use Solspace\Freeform\Library\Composer\Components\Attributes\DynamicNotificationAttributes;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Library\Composer\Components\Properties\ConnectionProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\FormProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\IntegrationProperties;
use Solspace\Freeform\Library\Composer\Components\Properties\ValidationProperties;
use Solspace\Freeform\Library\Database\FieldHandlerInterface;
use Solspace\Freeform\Library\Database\FormHandlerInterface;
use Solspace\Freeform\Library\Database\SpamSubmissionHandlerInterface;
use Solspace\Freeform\Library\Database\SubmissionHandlerInterface;
use Solspace\Freeform\Library\DataObjects\FormActionInterface;
use Solspace\Freeform\Library\DataObjects\Relations;
use Solspace\Freeform\Library\DataObjects\Suppressors;
use Solspace\Freeform\Library\Exceptions\Composer\ComposerException;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Library\FileUploads\FileUploadHandlerInterface;
use Solspace\Freeform\Library\FormTypes\FormTypeInterface;
use Solspace\Freeform\Library\Helpers\ReCaptchaHelper;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use Solspace\Freeform\Library\Rules\RuleProperties;
use Solspace\Freeform\Library\Translations\TranslatorInterface;
use Solspace\Freeform\Models\FormModel;
use Solspace\Freeform\Models\Settings;
use Twig\Markup;
use yii\base\Arrayable;
use yii\base\Event;
use yii\web\Request;

abstract class Form implements FormTypeInterface, \JsonSerializable, \Iterator, \ArrayAccess, Arrayable, \Countable
{
    public const ID_KEY = 'id';
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
    public const EVENT_OUTPUT_AS_JSON = 'output-as-json';
    public const EVENT_SET_PROPERTIES = 'set-properties';

    /** @deprecated use EVENT_SET_PROPERTIES instead. */
    public const EVENT_UPDATE_ATTRIBUTES = 'update-attributes';
    public const EVENT_SUBMIT = 'submit';
    public const EVENT_AFTER_SUBMIT = 'after-submit';
    public const EVENT_BEFORE_VALIDATE = 'before-validate';
    public const EVENT_AFTER_VALIDATE = 'after-validate';
    public const EVENT_ATTACH_TAG_ATTRIBUTES = 'attach-tag-attributes';
    public const EVENT_BEFORE_HANDLE_REQUEST = 'before-handle-request';
    public const EVENT_AFTER_HANDLE_REQUEST = 'after-handle-request';
    public const EVENT_BEFORE_RESET = 'before-reset-form';
    public const EVENT_AFTER_RESET = 'after-reset-form';
    public const EVENT_PERSIST_STATE = 'persist-state';
    public const EVENT_HYDRATE_FORM = 'hydrate-form';
    public const EVENT_GENERATE_RETURN_URL = 'generate-return-url';
    public const EVENT_PREPARE_AJAX_RESPONSE_PAYLOAD = 'prepare-ajax-response-payload';
    public const EVENT_CREATE_SUBMISSION = 'create-submission';
    public const EVENT_SEND_NOTIFICATIONS = 'send-notifications';
    public const EVENT_GET_CUSTOM_PROPERTY = 'get-custom-property';

    public const PROPERTY_STORED_VALUES = 'storedValues';
    public const PROPERTY_PAGE_INDEX = 'pageIndex';
    public const PROPERTY_PAGE_HISTORY = 'pageHistory';
    public const PROPERTY_SPAM_REASONS = 'spamReasons';

    public const SUCCESS_BEHAVIOUR_RELOAD = 'reload';
    public const SUCCESS_BEHAVIOUR_REDIRECT_RETURN_URL = 'redirect-return-url';
    public const SUCCESS_BEHAVIOUR_LOAD_SUCCESS_TEMPLATE = 'load-success-template';

    public const PAGE_INDEX_KEY = 'page_index';
    public const RETURN_URI_KEY = 'formReturnUrl';
    public const STATUS_KEY = 'formStatus';

    /** @deprecated will be removed in FF 4.x. Use EditSubmissionContext::TOKEN_KEY */
    public const SUBMISSION_TOKEN_KEY = 'formSubmissionToken';
    public const ELEMENT_ID_KEY = 'formElementId';
    public const DEFAULT_PAGE_INDEX = 0;

    public const DATA_DYNAMIC_TEMPLATE_KEY = 'dynamicTemplate';
    public const DATA_SUBMISSION_TOKEN = 'submissionToken';
    public const DATA_SUPPRESS = 'suppress';
    public const DATA_RELATIONS = 'relations';
    public const DATA_PERSISTENT_VALUES = 'persistentValues';
    public const DATA_DISABLE_RECAPTCHA = 'disableRecaptcha';

    public const LIMIT_COOKIE = 'cookie';
    public const LIMIT_IP_COOKIE = 'ip_cookie';

    /** @var PropertyBag */
    private $propertyBag;

    /** @var AttributeBag */
    private $attributeBag;

    /** @var int */
    private $id;

    /** @var string */
    private $uid;

    /** @var string */
    private $name;

    /** @var string */
    private $handle;

    /** @var array */
    private $metadata;

    /** @var string */
    private $color;

    /** @var string */
    private $submissionTitleFormat;

    /** @var string */
    private $description;

    /** @var string */
    private $returnUrl;

    /** @var bool */
    private $storeData;

    /** @var bool */
    private $ipCollectingEnabled;

    /** @var int */
    private $defaultStatus;

    /** @var string */
    private $formTemplate;

    /** @var Layout */
    private $layout;

    /** @var Page */
    private $currentPage;

    /** @var Row[] */
    private $currentPageRows;

    /** @var string */
    private $optInDataStorageTargetHash;

    /** @var string */
    private $limitFormSubmissions;

    /** @var Properties */
    private $properties;

    /** @var string[] */
    private $errors;

    private bool $submissionLimitReached;

    /** @var FormActionInterface[] */
    private $actions;

    /** @var bool */
    private $ajaxEnabled;

    /** @var bool */
    private $showSpinner;

    /** @var bool */
    private $showLoadingText;

    /** @var string */
    private $loadingText;

    /** @var bool */
    private $recaptchaEnabled;

    /** @var TranslatorInterface */
    private $translator;

    /** @var FreeformLogger */
    private $logger;

    /** @var bool */
    private $finished;

    /** @var bool */
    private $valid;

    /** @var bool */
    private $formSaved;

    /** @var bool */
    private $suppressionEnabled;

    /** @var bool */
    private $gtmEnabled;

    /** @var string */
    private $gtmId;

    /** @var string */
    private $gtmEventName;

    /** @var bool */
    private $disableAjaxReset;

    /** @var bool */
    private $pagePosted;

    /** @var bool */
    private $formPosted;

    private bool $graphqlPosted;

    private array $graphqlArguments;

    /**
     * Form constructor.
     *
     * @throws FreeformException
     * @throws ComposerException
     */
    public function __construct(
        FormModel $formModel,
        Properties $properties,
        array $layoutData,
        TranslatorInterface $translator,
        LoggerInterface $logger
    ) {
        $this->metadata = $formModel->metadata ?? [];
        $this->propertyBag = new PropertyBag($this);
        $this->attributeBag = new AttributeBag($this);

        $this->finished = false;
        $this->valid = false;
        $this->pagePosted = false;
        $this->formPosted = false;
        $this->graphqlPosted = false;
        $this->graphqlArguments = [];

        $this->properties = $properties;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->storeData = true;
        $this->ipCollectingEnabled = true;
        $this->errors = [];
        $this->submissionLimitReached = false;
        $this->suppressionEnabled = false;
        $this->gtmEnabled = false;
        $this->disableAjaxReset = false;

        $this->layout = new Layout(
            $this,
            $layoutData,
            $properties,
            $translator
        );

        $this->buildFromData(
            $properties->getFormProperties(),
            $properties->getValidationProperties()
        );

        $this->id = $formModel->id;
        $this->uid = $formModel->uid;

        $pageIndex = $this->propertyBag->get(self::PROPERTY_PAGE_INDEX, 0);
        $this->setCurrentPage($pageIndex);

        Event::trigger(self::class, self::EVENT_FORM_LOADED, new FormLoadedEvent($this));
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function __get(string $name)
    {
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

    /**
     * @return null|AbstractField
     */
    public function get(string $fieldHandle)
    {
        try {
            return $this->getLayout()->getFieldByHandle($fieldHandle);
        } catch (FreeformException $e) {
            try {
                return $this->getLayout()->getFieldByHash($fieldHandle);
            } catch (FreeformException $e) {
                try {
                    return $this->getLayout()->getSpecialField($fieldHandle);
                } catch (FreeformException $e) {
                    return null;
                }
            }
        }
    }

    public function getMetadata(string $key, $defaultValue = null)
    {
        return $this->metadata[$key] ?? $defaultValue;
    }

    public function hasFieldType(string $type): bool
    {
        return $this->getLayout()->hasFieldType($type);
    }

    public function getProperties(): PropertyBag
    {
        return $this->getPropertyBag();
    }

    public function getPropertyBag(): PropertyBag
    {
        return $this->propertyBag;
    }

    public function getAttributeBag(): AttributeBag
    {
        return $this->attributeBag;
    }

    public function getId()
    {
        return $this->id ? (int) $this->id : null;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    /**
     * @return null|string
     */
    public function getOptInDataStorageTargetHash()
    {
        return $this->optInDataStorageTargetHash;
    }

    /**
     * @return null|string
     */
    public function getLimitFormSubmissions()
    {
        return $this->limitFormSubmissions;
    }

    public function isLimitByIpCookie(): bool
    {
        return self::LIMIT_IP_COOKIE === $this->limitFormSubmissions;
    }

    public function getHash(): string
    {
        return $this->getPropertyBag()->get(self::HASH_KEY, '');
    }

    public function getSubmissionTitleFormat(): string
    {
        return $this->submissionTitleFormat;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCurrentPage(): Page
    {
        return $this->currentPage;
    }

    public function setCurrentPage(int $index): self
    {
        if (!$this->currentPage || $index !== $this->currentPage->getIndex()) {
            $page = $this->layout->getPage($index);

            $this->currentPage = $page;
            $this->currentPageRows = $page->getRows();
        }

        return $this;
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl ?: '';
    }

    /**
     * @deprecated will be removed in v4
     */
    public function getExtraPostUrl(): string
    {
        $bag = $this->getPropertyBag()->get(PayloadForwarding::BAG_KEY, []);

        return $bag[PayloadForwarding::KEY_URL] ?? '';
    }

    /**
     * @deprecated will be removed in v4
     */
    public function getExtraPostTriggerPhrase(): string
    {
        $bag = $this->getPropertyBag()->get(PayloadForwarding::BAG_KEY, []);

        return $bag[PayloadForwarding::KEY_TRIGGER_PHRASE] ?? '';
    }

    public function getAnchor(): string
    {
        $hash = $this->getHash();
        $id = $this->getPropertyBag()->get('id', $this->getId());
        $hashedId = substr(sha1($id.$this->getHandle()), 0, 6);

        return "{$hashedId}-form-{$hash}";
    }

    /**
     * @return null|int|string
     */
    public function getDefaultStatus()
    {
        return $this->defaultStatus;
    }

    public function getSuccessBehaviour(): string
    {
        return $this->getMetadata('successBehaviour', self::SUCCESS_BEHAVIOUR_RELOAD);
    }

    public function getSuccessTemplate(): ?string
    {
        return $this->getMetadata('successTemplate');
    }

    /**
     * @return int
     */
    public function isIpCollectingEnabled(): bool
    {
        return (bool) $this->ipCollectingEnabled;
    }

    public function isAjaxEnabled(): bool
    {
        return $this->ajaxEnabled;
    }

    public function isDisableAjaxReset(): bool
    {
        return $this->disableAjaxReset;
    }

    public function isShowSpinner(): bool
    {
        return $this->showSpinner;
    }

    public function isShowLoadingText(): bool
    {
        return $this->showLoadingText;
    }

    /**
     * @return null|string
     */
    public function getLoadingText()
    {
        return $this->loadingText;
    }

    /**
     * @throws ComposerException
     */
    public function getSuccessMessage(): string
    {
        return $this->getTranslator()->translate($this->getValidationProperties()->getSuccessMessage(), [], 'app');
    }

    /**
     * @throws ComposerException
     */
    public function getErrorMessage(): string
    {
        return $this->getTranslator()->translate($this->getValidationProperties()->getErrorMessage(), [], 'app');
    }

    // Only used for invisible recaptcha
    public function isRecaptchaEnabled(): bool
    {
        return $this->recaptchaEnabled;
    }

    public function isGtmEnabled(): bool
    {
        return (bool) $this->gtmEnabled;
    }

    public function getGtmId(): string
    {
        return $this->gtmId ?? '';
    }

    public function getGtmEventName(): string
    {
        return $this->gtmEventName ?? '';
    }

    public function isMultiPage(): bool
    {
        return \count($this->getPages()) > 1;
    }

    /**
     * @return Page[]
     */
    public function getPages(): array
    {
        return $this->layout->getPages();
    }

    /**
     * @return null|string
     */
    public function getFormTemplate()
    {
        return $this->formTemplate;
    }

    public function getLayout(): Layout
    {
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

    public function setSubmissionLimitReached(bool $submissionLimitReached): self
    {
        $this->submissionLimitReached = $submissionLimitReached;

        return $this;
    }

    public function isSubmissionLimitReached(): bool
    {
        return $this->submissionLimitReached;
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
        return $this->getPropertyBag()->get(self::PROPERTY_SPAM_REASONS, []);
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
        $bag = $this->getPropertyBag();

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

    public function isStoreData(): bool
    {
        return $this->storeData;
    }

    public function hasErrors(): bool
    {
        $errorCount = \count($this->getErrors());
        $errorCount += $this->getLayout()->getFieldErrorCount();

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

    /**
     * @deprecated use ::isPagePosted() or ::isFormPosted() instead
     */
    public function isSubmitted(): bool
    {
        return $this->isPagePosted();
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

    public function isFormPosted(): bool
    {
        return $this->formPosted;
    }

    public function setFormPosted(bool $formPosted): self
    {
        $this->formPosted = $formPosted;

        return $this;
    }

    public function isGraphQLPosted(): bool
    {
        return $this->graphqlPosted;
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

    public function getCustomAttributes(): PropertyBag
    {
        return $this->getPropertyBag();
    }

    public function handleRequest(Request $request): bool
    {
        $method = strtoupper($this->getPropertyBag()->get('method', 'post'));
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

    public function isSpamFolderEnabled(): bool
    {
        return $this->getFormHandler()->isSpamFolderEnabled() && $this->storeData;
    }

    public function processSpamSubmissionWithoutSpamFolder(): bool
    {
        if ($this->isLastPage()) {
            $this->formSaved = !$this->getFormHandler()->isSpamBehaviourReloadForm();

            return false;
        }

        return false;
    }

    /**
     * Returns list of mailing list fields that user opted-in.
     *
     * @return AbstractField[]
     */
    public function getMailingListOptedInFields(): array
    {
        $fields = [];

        $mailingListFields = $this->getLayout()->getFields(MailingListField::class);

        /** @var MailingListField $field */
        foreach ($mailingListFields as $field) {
            $isChecked = $field->isHidden() || (bool) $field->getValue();
            if ($isChecked && $field->getEmailFieldHash() && $field->getResourceId()) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    public function persistState()
    {
        Event::trigger(self::class, self::EVENT_PERSIST_STATE, new PersistStateEvent($this));
    }

    public function registerContext(array $renderProperties = null)
    {
        $this->setProperties($renderProperties);

        Event::trigger(self::class, self::EVENT_REGISTER_CONTEXT, new RegisterContextEvent($this));
    }

    /**
     * Render a predefined template.
     *
     * @param array $renderProperties
     *
     * @return null|Markup
     */
    public function render(array $renderProperties = null)
    {
        $this->setProperties($renderProperties);
        $formTemplate = $this->getPropertyBag()->get('formattingTemplate', $this->formTemplate);

        if (
            ($this->isSubmittedSuccessfully() || $this->isFinished())
            && self::SUCCESS_BEHAVIOUR_LOAD_SUCCESS_TEMPLATE === $this->getSuccessBehaviour()
        ) {
            return $this->getFormHandler()->renderSuccessTemplate($this);
        }

        return $this->getFormHandler()->renderFormTemplate($this, $formTemplate);
    }

    public function json(array $renderProperties = null): Markup
    {
        $this->registerContext($renderProperties);
        $bag = $this->getPropertyBag();

        $isMultipart = $this->getLayout()->hasFields(FileUploadInterface::class);

        $object = [
            'hash' => $this->getHash(),
            'handle' => $this->handle,
            // @deprecated Please use ajaxEnabled instead
            'ajax' => $this->isAjaxEnabled(),
            'ajaxEnabled' => $this->isAjaxEnabled(),
            'disableSubmit' => Freeform::getInstance()->forms->isFormSubmitDisable(),
            'disableReset' => $this->isDisableAjaxReset(),
            'showSpinner' => $this->isShowSpinner(),
            'showLoadingText' => $this->isShowLoadingText(),
            'loadingText' => $this->getLoadingText(),
            'class' => trim($bag->get('class', '')),
            'method' => $bag->get('method', 'post'),
            'enctype' => $isMultipart ? 'multipart/form-data' : 'application/x-www-form-urlencoded',
        ];

        $mailingLists = [];
        $mailingListFields = $this->getLayout()->getFields(MailingListField::class);
        if ($mailingListFields) {
            foreach ($mailingListFields as $mailingListField) {
                $mailingLists[] = $mailingListField->getContentGqlHandle();
            }
        }
        $object['mailingListName'] = $mailingLists;

        $reCaptchaEnabled = ReCaptchaHelper::canApplyReCaptcha($this);

        $settingsModel = Freeform::getInstance()->settings->getSettingsModel();

        $isHCaptcha = \in_array($settingsModel->getRecaptchaType(), [Settings::RECAPTCHA_TYPE_H_INVISIBLE, Settings::RECAPTCHA_TYPE_H_CHECKBOX], true);

        if ($reCaptchaEnabled) {
            $object['reCaptcha'] = [
                'enabled' => true,
                'handle' => 'reCaptcha',
                'name' => $isHCaptcha ? 'h-captcha-response' : 'g-recaptcha-response',
            ];
        } else {
            $object['reCaptcha'] = [
                'enabled' => false,
            ];
        }

        if ($this->getSuccessMessage()) {
            $object['successMessage'] = $this->getSuccessMessage();
        }

        if ($this->getErrorMessage()) {
            $object['errorMessage'] = $this->getErrorMessage();
        }

        $event = new OutputAsJsonEvent($this, $object);
        Event::trigger(self::class, self::EVENT_OUTPUT_AS_JSON, $event);
        $object = $event->getJsonObject();

        return Template::raw(json_encode((object) $object, \JSON_PRETTY_PRINT));
    }

    public function renderTag(array $renderProperties = null): Markup
    {
        $this->registerContext($renderProperties);

        $output = '';

        $beforeTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_BEFORE_OPEN_TAG, $beforeTag);
        $output .= $beforeTag->getChunksAsString();

        $attributes = $this->getAttributeBag()->jsonSerialize();
        $event = new AttachFormAttributesEvent($this, $attributes);
        Event::trigger(self::class, self::EVENT_ATTACH_TAG_ATTRIBUTES, $event);

        $attributes = array_merge(
            $event->getAttributes(),
            $this->getFormHandler()->onAttachFormAttributes($this, $event->getAttributes())
        );

        $compiledAttributes = StringHelper::compileAttributeStringFromArray($attributes);

        $output .= "<form {$compiledAttributes}>".\PHP_EOL;

        $hiddenFields = $this->layout->getFields(HiddenField::class);
        foreach ($hiddenFields as $field) {
            if ($field->getPageIndex() === $this->getCurrentPage()->getIndex()) {
                $output .= $field->renderInput();
            }
        }

        $output .= $this->getFormHandler()->onRenderOpeningTag($this);

        $afterTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_AFTER_OPEN_TAG, $afterTag);
        $output .= $afterTag->getChunksAsString();

        return Template::raw($output);
    }

    public function renderClosingTag(): Markup
    {
        $output = $this->getFormHandler()->onRenderClosingTag($this);

        $beforeTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_BEFORE_CLOSING_TAG, $beforeTag);
        $output .= $beforeTag->getChunksAsString();

        $output .= '</form>';

        $afterTag = new RenderTagEvent($this);
        Event::trigger(self::class, self::EVENT_RENDER_AFTER_CLOSING_TAG, $afterTag);
        $output .= $afterTag->getChunksAsString();

        return Template::raw($output);
    }

    public function getFormHandler(): FormHandlerInterface
    {
        return Freeform::getInstance()->forms;
    }

    public function getFieldHandler(): FieldHandlerInterface
    {
        return Freeform::getInstance()->fields;
    }

    public function getSubmissionHandler(): SubmissionHandlerInterface
    {
        return Freeform::getInstance()->submissions;
    }

    public function getSpamSubmissionHandler(): SpamSubmissionHandlerInterface
    {
        return Freeform::getInstance()->spamSubmissions;
    }

    public function getFileUploadHandler(): FileUploadHandlerInterface
    {
        return Freeform::getInstance()->files;
    }

    /**
     * @deprecated [Deprecated since v3.12] Instead use the ::getAttributeBag() bag
     */
    public function getTagAttributes(): array
    {
        return $this->getAttributeBag()->jsonSerialize();
    }

    public function getSuppressors(): Suppressors
    {
        $suppressors = $this->suppressionEnabled ? true : $this->getPropertyBag()->get(self::DATA_SUPPRESS);

        return new Suppressors($suppressors);
    }

    public function enableSuppression(): self
    {
        $this->suppressionEnabled = true;

        return $this;
    }

    public function getRelations(): Relations
    {
        return new Relations($this->getPropertyBag()->get(self::DATA_RELATIONS));
    }

    public function setProperties(array $properties = null): self
    {
        $this->propertyBag->merge($properties ?? []);

        Event::trigger(
            self::class,
            self::EVENT_SET_PROPERTIES,
            new SetPropertiesEvent($this, $properties ?? [])
        );

        return $this;
    }

    /**
     * @deprecated Use ::setProperties() instead. Will be removed in Freeform 4.x
     */
    public function setAttributes(array $attributes = null): self
    {
        $event = new UpdateAttributesEvent($this, $attributes ?? []);
        Event::trigger(self::class, self::EVENT_UPDATE_ATTRIBUTES, $event);

        return $this->setProperties($event->getAttributes());
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * @return null|CheckboxField
     */
    public function getOptInDataTargetField()
    {
        if ($this->optInDataStorageTargetHash) {
            $field = $this->get($this->optInDataStorageTargetHash);

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
        if ($this->getOptInDataTargetField()) {
            return (bool) $this->getOptInDataTargetField()->getValue();
        }

        return true;
    }

    public function hasFieldBeenSubmitted(AbstractField $field): bool
    {
        return isset($this->getPropertyBag()->get(self::PROPERTY_STORED_VALUES, [])[$field->getHandle()]);
    }

    public function reset()
    {
        $event = new ResetEvent($this);
        Event::trigger(self::class, self::EVENT_BEFORE_RESET, $event);

        if (!$event->isValid) {
            return;
        }

        Event::trigger(self::class, self::EVENT_AFTER_RESET, $event);
    }

    /**
     * @return Properties\ValidationProperties
     *
     * @throws ComposerException
     */
    public function getValidationProperties(): ValidationProperties
    {
        return $this->properties->getValidationProperties();
    }

    /**
     * @return Properties\AdminNotificationProperties
     *
     * @throws ComposerException
     */
    public function getAdminNotificationProperties()
    {
        return $this->properties->getAdminNotificationProperties();
    }

    /**
     * Returns data for dynamic notification email template.
     *
     * @return null|DynamicNotificationAttributes
     *
     * @throws FreeformException
     */
    public function getDynamicNotificationData()
    {
        $data = $this->getPropertyBag()->get(self::DATA_DYNAMIC_TEMPLATE_KEY);
        if ($data) {
            return new DynamicNotificationAttributes($data);
        }

        return null;
    }

    /**
     * Returns the assigned submission token.
     *
     * @deprecated will be removed in FF 4.x. Use EditSubmissionContext::getToken($form) instead.
     *
     * @return null|string
     */
    public function getAssociatedSubmissionToken()
    {
        return EditSubmissionContext::getToken($this);
    }

    /**
     * @return null|string
     */
    public function getFieldPrefix()
    {
        return $this->getPropertyBag()->get('fieldIdPrefix');
    }

    /**
     * Returns form CRM integration properties.
     *
     * @return Properties\IntegrationProperties
     */
    public function getIntegrationProperties(): IntegrationProperties
    {
        return $this->properties->getIntegrationProperties();
    }

    /**
     * Returns form payment integration properties.
     *
     * @return Properties\PaymentProperties
     */
    public function getPaymentProperties()
    {
        return $this->properties->getPaymentProperties();
    }

    /**
     * Returns form CRM integration properties.
     *
     * @return Properties\ConnectionProperties
     */
    public function getConnectionProperties(): ConnectionProperties
    {
        return $this->properties->getConnectionProperties();
    }

    /**
     * Returns form field rule properties.
     *
     * @return null|RuleProperties
     */
    public function getRuleProperties()
    {
        return $this->properties->getRuleProperties();
    }

    // ==========================
    // INTERFACE IMPLEMENTATIONS
    // ==========================

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'uid' => $this->getUid(),
            'name' => $this->name,
            'handle' => $this->handle,
            'color' => $this->color,
            'description' => $this->description,
            'returnUrl' => $this->returnUrl,
            'storeData' => (bool) $this->storeData,
            'defaultStatus' => $this->defaultStatus,
            'formTemplate' => $this->formTemplate,
            'optInDataStorageTargetHash' => $this->optInDataStorageTargetHash,
            'limitFormSubmissions' => $this->limitFormSubmissions,
            'submissionLimitReached' => $this->submissionLimitReached,
        ];
    }

    public function current(): false|Row
    {
        return current($this->currentPageRows);
    }

    public function next(): void
    {
        next($this->currentPageRows);
    }

    public function key(): ?int
    {
        return key($this->currentPageRows);
    }

    public function valid(): bool
    {
        return null !== $this->key() && false !== $this->key();
    }

    public function rewind(): void
    {
        reset($this->currentPageRows);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->currentPageRows[$offset]);
    }

    public function offsetGet(mixed $offset): ?Row
    {
        return $this->offsetExists($offset) ? $this->currentPageRows[$offset] : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new FreeformException('Form ArrayAccess does not allow for setting values');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new FreeformException('Form ArrayAccess does not allow unsetting values');
    }

    public function count(): int
    {
        return \count($this->currentPageRows);
    }

    public function isLastPage(): bool
    {
        $currentPageIndex = $this->getPropertyBag()->get(self::PROPERTY_PAGE_INDEX, 0);

        return $currentPageIndex === (\count($this->getPages()) - 1);
    }

    /**
     * {@inheritDoc}
     */
    public function fields()
    {
        return array_keys($this->jsonSerialize());
    }

    /**
     * {@inheritDoc}
     */
    public function extraFields()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(array $fields = [], array $expand = [], $recursive = true)
    {
        return $this->jsonSerialize();
    }

    private function validate()
    {
        $event = new ValidationEvent($this);
        Event::trigger(self::class, self::EVENT_BEFORE_VALIDATE, $event);

        if (!$event->isValid) {
            $this->valid = $event->getValidationOverride();

            return;
        }

        $this->getFormHandler()->onFormValidate($this);

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
            if (!$field->isValid()) {
                $isFormValid = false;
            }
        }

        if ($this->errors) {
            $isFormValid = false;
        }

        if ($isFormValid) {
            foreach ($currentPageFields as $field) {
                if ($field instanceof FileUploadInterface) {
                    try {
                        $field->uploadFile();
                    } catch (\Exception $e) {
                        $isFormValid = false;
                        $this->logger->error($e->getMessage(), ['field' => $field]);
                    }

                    if ($field->hasErrors()) {
                        $isFormValid = false;
                    }
                }
            }
        }

        $this->getFormHandler()->onAfterFormValidate($this);

        $this->valid = $isFormValid;

        $event = new ValidationEvent($this);
        Event::trigger(self::class, self::EVENT_AFTER_VALIDATE, $event);

        if (!$event->isValid) {
            $this->valid = $event->getValidationOverride();
        }
    }

    /**
     * Builds the form object based on $formData.
     */
    private function buildFromData(FormProperties $formProperties, ValidationProperties $validationProperties)
    {
        $this->name = $formProperties->getName();
        $this->handle = $formProperties->getHandle();
        $this->color = $formProperties->getColor();
        $this->submissionTitleFormat = $formProperties->getSubmissionTitleFormat();
        $this->description = $formProperties->getDescription();
        $this->returnUrl = $formProperties->getReturnUrl();
        $this->storeData = $formProperties->isStoreData();
        $this->ipCollectingEnabled = $formProperties->isIpCollectingEnabled();
        $this->defaultStatus = $formProperties->getDefaultStatus();
        $this->formTemplate = $formProperties->getFormTemplate();
        $this->optInDataStorageTargetHash = $formProperties->getOptInDataStorageTargetHash();
        $this->limitFormSubmissions = $validationProperties->getLimitFormSubmissions();
        $this->ajaxEnabled = $formProperties->isAjaxEnabled();
        $this->showSpinner = $validationProperties->isShowSpinner();
        $this->showLoadingText = $validationProperties->isShowLoadingText();
        $this->loadingText = $validationProperties->getLoadingText();
        $this->recaptchaEnabled = $formProperties->isRecaptchaEnabled();
        $this->gtmEnabled = $formProperties->isGtmEnabled();
        $this->gtmId = $formProperties->getGtmId();
        $this->gtmEventName = $formProperties->getGtmEventName();

        $event = new HydrateEvent($this, $formProperties, $validationProperties);
        Event::trigger(self::class, self::EVENT_HYDRATE_FORM, $event);

        $this->getAttributeBag()->merge(CustomFormAttributes::extractAttributes($formProperties->getTagAttributes()));
    }
}
