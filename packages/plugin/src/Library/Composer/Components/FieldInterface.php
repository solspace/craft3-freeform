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

use Solspace\Freeform\Library\Composer\Components\Validation\Constraints\ConstraintInterface;
use Twig\Markup;

interface FieldInterface
{
    public const EVENT_TRANSFORM_FROM_POST = 'transform-from-post';
    public const EVENT_TRANSFORM_FROM_STORAGE = 'transform-from-storage';
    public const EVENT_TRANSFORM_FROM_DATABASE = 'transform-from-database';

    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_HIDDEN = 'hidden';
    public const TYPE_SELECT = 'select';
    public const TYPE_MULTIPLE_SELECT = 'multiple_select';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_CHECKBOX_GROUP = 'checkbox_group';
    public const TYPE_RADIO_GROUP = 'radio_group';
    public const TYPE_HTML = 'html';
    public const TYPE_SUBMIT = 'submit';
    public const TYPE_SAVE = 'save';
    public const TYPE_DYNAMIC_RECIPIENTS = 'dynamic_recipients';
    public const TYPE_EMAIL = 'email';
    public const TYPE_MAILING_LIST = 'mailing_list';
    public const TYPE_FILE = 'file';
    public const TYPE_FILE_DRAG_AND_DROP = 'file_drag_and_drop';
    public const TYPE_PASSWORD = 'password';

    public const TYPE_RICH_TEXT = 'rich_text';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_NUMBER = 'number';
    public const TYPE_PHONE = 'phone';
    public const TYPE_WEBSITE = 'website';
    public const TYPE_RATING = 'rating';
    public const TYPE_REGEX = 'regex';
    public const TYPE_CONFIRMATION = 'confirmation';
    public const TYPE_RECAPTCHA = 'recaptcha';
    public const TYPE_OPINION_SCALE = 'opinion_scale';
    public const TYPE_SIGNATURE = 'signature';
    public const TYPE_TABLE = 'table';
    public const TYPE_INVISIBLE = 'invisible';

    public const TYPE_CREDIT_CARD_DETAILS = 'cc_details';
    public const TYPE_CREDIT_CARD_NUMBER = 'cc_number';
    public const TYPE_CREDIT_CARD_EXPIRY = 'cc_expiry';
    public const TYPE_CREDIT_CARD_CVC = 'cc_cvc';

    /**
     * Returns the INPUT type.
     */
    public function getType(): string;

    public function getValue(): mixed;

    /**
     * Gets whatever value is set and returns its string representation.
     */
    public function getValueAsString(): string;

    public function getId(): ?int;

    public function getHandle(): ?string;

    public function getContentGqlHandle(): ?string;

    public function getHash(): string;

    public function setValue(mixed $value): self;

    public function getErrors(): ?array;

    public function hasErrors(): bool;

    public function addError(string $error): AbstractField;

    /**
     * Renders the <label> and <input> tags combined.
     */
    public function render(): Markup;

    /**
     * Renders the <label> tag.
     */
    public function renderLabel(): Markup;

    /**
     * Outputs the HTML of input.
     */
    public function renderInput(): Markup;

    /**
     * Outputs the HTML of errors.
     */
    public function renderErrors(): Markup;

    public function isValid(): bool;

    public function canRender(): bool;

    public function canStoreValues(): bool;

    /**
     * @return ConstraintInterface[]
     */
    public function getConstraints(): array;
}
