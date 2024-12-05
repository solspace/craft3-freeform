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

namespace Solspace\Freeform\Fields;

use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Attributes\FieldAttributesCollection;
use Twig\Markup;

interface FieldInterface
{
    public const EVENT_TRANSFORM_FROM_POST = 'transform-from-post';
    public const EVENT_TRANSFORM_FROM_STORAGE = 'transform-from-storage';
    public const EVENT_TRANSFORM_FOR_STORAGE = 'transform-for-storage';
    public const EVENT_TRANSFORM_FROM_DATABASE = 'transform-from-database';

    public const EVENT_BEFORE_SAVE = 'before-save';
    public const EVENT_AFTER_SAVE = 'after-save';

    public const EVENT_RENDER_CONTAINER = 'render-container';
    public const EVENT_RENDER_INPUT = 'render-input';
    public const EVENT_RENDER_LABEL = 'render-label';
    public const EVENT_RENDER_ERRORS = 'render-errors';
    public const EVENT_RENDER_INSTRUCTIONS = 'render-instructions';
    public const EVENT_COMPILE_ATTRIBUTES = 'compile-attributes';
    public const EVENT_SET_PARAMETERS = 'set-parameters';

    public const EVENT_VALIDATE = 'validate';
    public const EVENT_AFTER_SET_PROPERTIES = 'after-set-properties';

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_HIDDEN = 'hidden';
    public const TYPE_SELECT = 'dropdown';
    public const TYPE_MULTIPLE_SELECT = 'multiple-select';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_CHECKBOX_GROUP = 'checkboxes';
    public const TYPE_CALCULATION = 'calculation';
    public const TYPE_RADIO_GROUP = 'radios';
    public const TYPE_HTML = 'html';
    public const TYPE_SUBMIT = 'submit';
    public const TYPE_SAVE = 'save';
    public const TYPE_DYNAMIC_RECIPIENTS = 'dynamic-recipients';
    public const TYPE_EMAIL = 'email';
    public const TYPE_FILE = 'file';
    public const TYPE_FILE_DRAG_AND_DROP = 'file-dnd';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_RICH_TEXT = 'rich-text';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_NUMBER = 'number';
    public const TYPE_PHONE = 'phone';
    public const TYPE_WEBSITE = 'website';
    public const TYPE_RATING = 'rating';
    public const TYPE_REGEX = 'regex';
    public const TYPE_CONFIRMATION = 'confirm';
    public const TYPE_OPINION_SCALE = 'opinion-scale';
    public const TYPE_SIGNATURE = 'signature';
    public const TYPE_TABLE = 'table';
    public const TYPE_INVISIBLE = 'invisible';
    public const TYPE_GROUP = 'group';
    public const TYPE_CREDIT_CARD_DETAILS = 'credit-card';
    public const TYPE_CREDIT_CARD_NUMBER = 'cc-number';
    public const TYPE_CREDIT_CARD_EXPIRY = 'cc-expiry';
    public const TYPE_CREDIT_CARD_CVC = 'cc-cvc';

    public function getForm(): Form;

    public function getType(): string;

    public function getValue(): mixed;

    public function setValue(mixed $value): self;

    public function getValueAsString(): string;

    public function getId(): ?int;

    public function getUid(): ?string;

    public function getRowId(): ?int;

    public function getRowUid(): ?string;

    public function getOrder(): ?int;

    public function getHandle(): ?string;

    public function getContentGqlHandle(): ?string;

    public function getLabel(): ?string;

    public function getInstructions(): ?string;

    public function isRequired(): bool;

    public function getRequiredErrorMessage(): ?string;

    public function getErrors(): ?array;

    public function hasErrors(): bool;

    public function addError(...$error): AbstractField;

    public function addErrors(array $errors): AbstractField;

    public function render(): Markup;

    public function renderLabel(): Markup;

    public function renderInput(): Markup;

    public function renderErrors(): Markup;

    public function isValid(): bool;

    public function canRender(): bool;

    public function canStoreValues(): bool;

    public function getContentGqlDescription(): array;

    public function getContentGqlType(): array|Type;

    public function getContentGqlMutationArgumentType(): array|Type;

    public function includeInGqlSchema(): bool;

    public function setParameters(?array $parameters = null): void;

    public function getAttributes(): FieldAttributesCollection;

    public function implements(string ...$interfaces): bool;
}
