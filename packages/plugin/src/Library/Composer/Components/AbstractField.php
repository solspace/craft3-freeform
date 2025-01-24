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
use GraphQL\Type\Definition\Type;
use Solspace\Commons\Helpers\StringHelper;
use Solspace\Freeform\Fields\CheckboxField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\Attributes\CustomFieldAttributes;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoRenderInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\ObscureValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\StaticValueInterface;
use Solspace\Freeform\Library\Composer\Components\Properties\FieldProperties;
use Solspace\Freeform\Library\Composer\Components\Validation\Constraints\ConstraintInterface;
use Solspace\Freeform\Library\Composer\Components\Validation\Validator;
use Stringy\Stringy;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Twig\Markup;

abstract class AbstractField implements FieldInterface, \JsonSerializable
{
    /** @var string */
    public $handle;

    /** @var string */
    public $instructions;

    /** @var string */
    protected $hash;

    /** @var int */
    protected $id;

    /** @var string */
    protected $label;

    /** @var bool */
    protected $required = false;

    /** @var CustomFieldAttributes */
    protected $customAttributes;

    /** @var int */
    protected $pageIndex;

    /** @var array */
    protected $errors;

    /** @var array */
    protected $inputAttributes;

    /** @var array */
    protected $labelAttributes;

    /** @var array */
    protected $errorAttributes;

    /** @var array */
    protected $instructionAttributes;

    /** @var Form */
    private $form;

    /** @var mimxed */
    private $defaultValue;

    /** @var array */
    private $inputClasses;

    /**
     * AbstractField constructor.
     */
    final public function __construct(Form $form)
    {
        $this->form = $form;
        $this->customAttributes = new CustomFieldAttributes($this, [], $this->getForm()->getPropertyBag());
        $this->inputClasses = [];
    }

    public function __toString(): string
    {
        return $this->getValueAsString();
    }

    /**
     * @param int $pageIndex
     */
    final public static function createFromProperties(
        Form $form,
        FieldProperties $properties,
        $pageIndex
    ): self {
        $calledClass = static::class;

        $objectProperties = get_class_vars($calledClass);
        $accessor = PropertyAccess::createPropertyAccessor();

        $field = new static($form);
        $field->pageIndex = $pageIndex;

        foreach ($objectProperties as $fieldName => $type) {
            if ('errors' === $fieldName) {
                continue;
            }

            try {
                $value = $accessor->getValue($properties, $fieldName);
                $field->{$fieldName} = $value;
                if ('value' === $fieldName) {
                    if ($field instanceof CheckboxField && !$field->isChecked()) {
                        $field->setValue('');
                    }
                    $field->defaultValue = $value;
                } elseif ('values' === $fieldName) {
                    $field->defaultValue = $value;
                }
            } catch (NoSuchPropertyException $e) {
                // Pass along
            }
        }

        if ($field instanceof StaticValueInterface) {
            $field->staticValue = $field->getValue();
        }

        return $field;
    }

    public static function getFieldTypes(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_TEXTAREA => 'Textarea',
            self::TYPE_EMAIL => 'Email',
            self::TYPE_HIDDEN => 'Hidden',
            self::TYPE_SELECT => 'Select',
            self::TYPE_MULTIPLE_SELECT => 'Multi select',
            self::TYPE_CHECKBOX => 'Checkbox',
            self::TYPE_CHECKBOX_GROUP => 'Checkbox group',
            self::TYPE_RADIO_GROUP => 'Radio group',
            self::TYPE_FILE => 'File Upload',
            self::TYPE_FILE_DRAG_AND_DROP => 'File Drag & Drop',
            self::TYPE_DYNAMIC_RECIPIENTS => 'Dynamic Recipients',
            self::TYPE_DATETIME => 'Date & Time',
            self::TYPE_NUMBER => 'Number',
            self::TYPE_PHONE => 'Phone',
            self::TYPE_WEBSITE => 'Website',
            self::TYPE_RATING => 'Rating',
            self::TYPE_REGEX => 'Regex',
            self::TYPE_CONFIRMATION => 'Confirmation',
            self::TYPE_OPINION_SCALE => 'Opinion Scale',
            self::TYPE_SIGNATURE => 'Signature',
            self::TYPE_TABLE => 'Table',
            self::TYPE_INVISIBLE => 'Invisible',
        ];
    }

    public static function getFieldTypeName(): string
    {
        return (string) Stringy::create(static::getFieldType())->humanize();
    }

    public static function getFieldType(): string
    {
        $name = (new \ReflectionClass(static::class))->getShortName();
        $name = str_replace('Field', '', $name);

        return (string) Stringy::create($name)->underscored();
    }

    /**
     * Render the complete set of HTML for this field
     * That includes the Label, Input and Error messages.
     *
     * @param array $customAttributes
     */
    final public function render(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        $output = '';
        if (!$this instanceof InputOnlyInterface) {
            $output .= $this->getLabelHtml();
        }

        // Show instructions above by default
        if (!$this->getCustomAttributes()->isInstructionsBelowField()) {
            $output .= $this->getInstructionsHtml();
        }

        $output .= $this->onBeforeInputHtml();
        $output .= $this->getInputHtml();
        $output .= $this->onAfterInputHtml();

        // Show instructions below only if set by a property
        if ($this->getCustomAttributes()->isInstructionsBelowField()) {
            $output .= $this->getInstructionsHtml();
        }

        if ($this->getErrors()) {
            $output .= $this->renderErrors();
        }

        return $this->renderRaw($output);
    }

    /**
     * Render the Label HTML.
     *
     * @param array $customAttributes
     */
    final public function renderLabel(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        return $this->renderRaw($this->getLabelHtml());
    }

    public function renderInstructions(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        return $this->renderRaw($this->getInstructionsHtml());
    }

    /**
     * Render the Input HTML.
     *
     * @param array $customAttributes
     */
    final public function renderInput(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        return $this->renderRaw($this->getInputHtml());
    }

    /**
     * Outputs the HTML of errors.
     *
     * @param array $customAttributes
     */
    final public function renderErrors(array $customAttributes = null): Markup
    {
        $this->setCustomAttributes($customAttributes);

        return $this->renderRaw($this->getErrorHtml());
    }

    public function getRules(): string|null
    {
        $ruleProperties = $this->getForm()->getRuleProperties();
        if (null === $ruleProperties) {
            return null;
        }

        $rule = $ruleProperties->getFieldRule($this->getPageIndex(), $this->getHash());
        if (null === $rule) {
            return null;
        }

        return json_encode($rule, \JSON_HEX_APOS);
    }

    final public function rulesHtmlData(): Markup
    {
        $data = $this->getRules();

        if (null === $data) {
            return $this->renderRaw('');
        }

        return $this->renderRaw(" data-ff-rule='{$data}'");
    }

    final public function canRender(): bool
    {
        return !$this instanceof NoRenderInterface;
    }

    final public function canStoreValues(): bool
    {
        return !$this instanceof NoStorageInterface;
    }

    public function isInputOnly(): bool
    {
        return $this instanceof InputOnlyInterface;
    }

    /**
     * Validates the Field value.
     */
    public function isValid(): bool
    {
        $this->addErrors($this->validate());

        return empty($this->errors);
    }

    /**
     * Returns an array of error messages.
     */
    public function getErrors(): array
    {
        if (null === $this->errors) {
            $this->errors = [];
        }

        return array_values($this->errors);
    }

    public function hasErrors(): bool
    {
        $errors = $this->getErrors();

        return !empty($errors);
    }

    /**
     * @return $this
     */
    public function addErrors(array $errors = null): self
    {
        if (empty($errors)) {
            return $this;
        }

        $existingErrors = $this->getErrors();
        $existingErrors = array_merge($existingErrors, $errors);

        $existingErrors = array_unique($existingErrors);

        $this->errors = $existingErrors;

        return $this;
    }

    /**
     * @return $this
     */
    public function addError(string $error): self
    {
        $this->addErrors([$error]);

        return $this;
    }

    /**
     * Return the field TYPE.
     */
    abstract public function getType(): string;

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getLabel(): string
    {
        if (empty($this->label)) {
            $field = Freeform::getInstance()->fields->getFieldById($this->getId());
            if ($field) {
                return $this->translate($field->label);
            }

            return $this->getHandle();
        }

        return $this->translate($this->label);
    }

    public function getInstructions(): string
    {
        return $this->translate($this->instructions);
    }

    public function isRequired(): bool
    {
        return (bool) $this->required;
    }

    public function isHidden(): bool
    {
        static $rules;

        if (null === $rules) {
            $rules = $this->getForm()->getRuleProperties();
            if (null === $rules) {
                $rules = false;
            }
        }

        if (false === $rules) {
            return false;
        }

        return $rules->isHidden($this, $this->getForm());
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getPageIndex(): int
    {
        return $this->pageIndex;
    }

    /**
     * Gets whatever value is set and returns its string representation.
     */
    public function getValueAsString(bool $optionsAsValues = true): string
    {
        $value = $this->getValue();

        if (!\is_string($value)) {
            if (\is_array($value)) {
                return StringHelper::implodeRecursively(', ', $value);
            }

            return (string) $value;
        }

        return $value;
    }

    /**
     * Either gets the ID attribute specified in custom attributes
     * or generates a new one: "form-input-{handle}".
     */
    public function getIdAttribute(): string
    {
        $attribute = sprintf('form-input-%s', $this->getHandle());

        if ($this->getCustomAttributes()->getId()) {
            $attribute = $this->getCustomAttributes()->getId();
        }

        return $this->getCustomAttributes()->getFieldIdPrefix().$attribute;
    }

    /**
     * An alias for ::setCustomAttributes().
     */
    public function setAttributes(array $attributes = null)
    {
        $this->setCustomAttributes($attributes);
    }

    /**
     * @return ConstraintInterface[]
     */
    public function getConstraints(): array
    {
        return [];
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addInputAttribute(string $name = null, string $value = null): self
    {
        $this->inputAttributes[sha1($name.$value)] = ['attribute' => $name, 'value' => $value];

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addLabelAttribute(string $name = null, string $value = null): self
    {
        $this->labelAttributes[] = ['attribute' => $name, 'value' => $value];

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addErrorAttribute(string $name = null, string $value = null): self
    {
        $this->errorAttributes[] = ['attribute' => $name, 'value' => $value];

        return $this;
    }

    /**
     * @param string $name
     * @param string $value
     */
    public function addInstructionAttribute(string $name = null, string $value = null): self
    {
        $this->instructionAttributes[] = ['attribute' => $name, 'value' => $value];

        return $this;
    }

    public function getInputAttributes(): array
    {
        return $this->inputAttributes ?? [];
    }

    final public function getInputAttributesString(): string
    {
        return $this->assembleAttributeString($this->inputAttributes ?? []);
    }

    public function getLabelAttributes(): array
    {
        return $this->labelAttributes ?? [];
    }

    final public function getLabelAttributesString(): string
    {
        return $this->assembleAttributeString($this->labelAttributes ?? []);
    }

    public function getErrorAttributes(): array
    {
        return $this->errorAttributes ?? [];
    }

    final public function getErrorAttributesString(): string
    {
        return $this->assembleAttributeString($this->errorAttributes ?? []);
    }

    public function getInstructionAttributes(): array
    {
        return $this->instructionAttributes ?? [];
    }

    final public function getInstructionAttributesString(): string
    {
        return $this->assembleAttributeString($this->instructionAttributes ?? []);
    }

    public function jsonSerialize(): string
    {
        return $this->hash;
    }

    public function getContentGqlDescription(): array
    {
        $description = [];
        $description[] = $this->getInstructions();

        if ($this->isRequired()) {
            $description[] = 'Value is required.';
        }

        return $description;
    }

    public function getContentGqlType(): Type|array
    {
        return Type::string();
    }

    public function getContentGqlHandle(): ?string
    {
        return $this->getHandle();
    }

    public function getContentGqlMutationArgumentType(): Type|array
    {
        $description = $this->getContentGqlDescription();
        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    public function includeInGqlSchema(): bool
    {
        return true;
    }

    protected function getInputClassString(): string
    {
        return implode(' ', $this->inputClasses);
    }

    /**
     * @param string $class
     *
     * @return $this
     */
    protected function addInputClass($class)
    {
        $this->inputClasses[] = $class;

        return $this;
    }

    /**
     * Assemble the Label HTML string.
     */
    protected function getLabelHtml(): string
    {
        $this->addLabelAttribute('class', $this->getCustomAttributes()->getLabelClass());

        $forAttribute = sprintf(' for="%s"', $this->getIdAttribute());

        $output = '<label'.$forAttribute.$this->getLabelAttributesString().'>';
        $output .= (empty($this->label)) ? '&nbsp;' : $this->getLabel();
        $output .= '</label>';
        $output .= \PHP_EOL;

        return $output;
    }

    /**
     * Assemble the Instructions HTML string.
     */
    protected function getInstructionsHtml(): string
    {
        if (!$this->getInstructions()) {
            return '';
        }

        $this->addInstructionAttribute('class', $this->getCustomAttributes()->getInstructionsClass());

        $output = '<div'.$this->getInstructionAttributesString().'>';
        $output .= $this->getInstructions();
        $output .= '</div>';
        $output .= \PHP_EOL;

        return $output;
    }

    /**
     * Assemble the Error HTML output string.
     */
    protected function getErrorHtml(): string
    {
        $errors = $this->getErrors();
        if (empty($errors)) {
            return '';
        }

        $this
            ->addErrorAttribute('class', 'errors')
            ->addErrorAttribute('class', $this->getCustomAttributes()->getErrorClass())
        ;

        $output = '<ul'.$this->getErrorAttributesString().'>';

        foreach ($errors as $error) {
            if (\is_array($error)) {
                $error = implode(', ', $error);
            }

            $output .= '<li>'.htmlentities($error).'</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    /**
     * @return CustomFieldAttributes
     */
    protected function getCustomAttributes(): Attributes\CustomFieldAttributes
    {
        return $this->customAttributes;
    }

    /**
     * Outputs ' $name="$value"' where the $value is escaped
     * using htmlspecialchars() if $escapeValue is TRUE.
     *
     * @param mixed $value
     */
    protected function getAttributeString(string $name, $value, bool $escapeValue = true, bool $insertEmpty = false): string
    {
        if ('' !== $value || $insertEmpty) {
            return sprintf(
                ' %s="%s"',
                $name,
                $escapeValue ? htmlentities($value) : $value
            );
        }

        return '';
    }

    /**
     * Outputs ' $name' if $enabled is true.
     */
    protected function getParameterString(string $name, bool $enabled): string
    {
        return $enabled ? sprintf(' %s', $name) : '';
    }

    /**
     * Outputs ' $name="$value"' where the $value is a number.
     */
    protected function getNumericAttributeString(string $name, int $value = null): string
    {
        if (null !== $value && 0 !== $value) {
            return sprintf(' %s="%s"', $name, $value);
        }

        return '';
    }

    protected function getRequiredAttribute(): string
    {
        $attribute = '';

        if ($this->isRequired()) {
            $attribute = ' data-required';

            if ($this->getCustomAttributes()->getUseRequiredAttribute()) {
                $attribute = ' required';
            }
        }

        return $attribute;
    }

    /**
     * Assemble the Input HTML string.
     */
    abstract protected function getInputHtml(): string;

    /**
     * Output something before an input HTML is output.
     */
    protected function onBeforeInputHtml(): string
    {
        return '';
    }

    /**
     * Output something after an input HTML is output.
     */
    protected function onAfterInputHtml(): string
    {
        return '';
    }

    /**
     * Validate the field and add error messages if any.
     */
    protected function validate(): array
    {
        $form = $this->getForm();
        $form->getFieldHandler()->beforeValidate($this, $form);

        $errors = $this->getErrors();

        if ($this instanceof ObscureValueInterface) {
            $value = $this->getActualValue($this->getValue());
        } else {
            $value = $this->getValue();
        }

        if ($this->isRequired() && !$this->isHidden()) {
            if (\is_array($value)) {
                $value = array_filter($value);

                if (empty($value)) {
                    $errors[] = $this->translate('This field is required');
                }
            } elseif (null === $value || '' === trim($value)) {
                $errors[] = $this->translate('This field is required');
            }
        }

        if ('' !== $value && !$this->isHidden()) {
            static $validator;

            if (null === $validator) {
                $validator = new Validator();
            }

            $violationList = $validator->validate($this, $value);

            $errors = array_merge($errors, $violationList->getErrors());
        }

        $form->getFieldHandler()->afterValidate($this, $form);

        return $errors;
    }

    protected function getForm(): Form
    {
        return $this->form;
    }

    /**
     * An alias method for translator.
     *
     * @param string $string
     */
    protected function translate(string $string = null, array $variables = []): string
    {
        return null === $string ? '' : $this->getForm()->getTranslator()->translate($string, $variables);
    }

    /**
     * @param string $output
     */
    protected function renderRaw($output): Markup
    {
        return Template::raw($output);
    }

    /**
     * Sets the custom field attributes.
     */
    protected function setCustomAttributes(array $attributes = null)
    {
        if (null !== $attributes) {
            $this->customAttributes->mergeAttributes($attributes);
        }
    }

    private function assembleAttributeString(array $attributes): string
    {
        return CustomFieldAttributes::extractAttributeString(
            $attributes,
            $this,
            [
                'form' => $this->getForm(),
                'field' => $this,
            ]
        );
    }
}
