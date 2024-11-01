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

use craft\helpers\Template;
use GraphQL\Type\Definition\Type;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\Attributes\FieldAttributesTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Limitation;
use Solspace\Freeform\Attributes\Property\Middleware;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\Translatable;
use Solspace\Freeform\Attributes\Property\Validators;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Bundles\Fields\ImplementationProvider;
use Solspace\Freeform\Events\Fields\CompileFieldAttributesEvent;
use Solspace\Freeform\Events\Fields\FieldRenderEvent;
use Solspace\Freeform\Events\Fields\SetParametersEvent;
use Solspace\Freeform\Events\Fields\ValidateEvent;
use Solspace\Freeform\Fields\Interfaces\InputOnlyInterface;
use Solspace\Freeform\Fields\Interfaces\NoRenderInterface;
use Solspace\Freeform\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Fields\Parameters\Parameters;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Attributes\Attributes;
use Solspace\Freeform\Library\Attributes\FieldAttributesCollection;
use Solspace\Freeform\Library\Exceptions\FieldExceptions\FieldException;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Library\Serialization\Normalizers\IdentificatorInterface;
use Solspace\Freeform\Services\Form\TranslationsService;
use Symfony\Component\Serializer\Annotation\Ignore;
use Twig\Markup;
use yii\base\Event;

/**
 * @template T
 */
abstract class AbstractField implements FieldInterface, IdentificatorInterface
{
    #[Translatable]
    #[Section(
        handle: 'general',
        label: 'General',
        icon: __DIR__.'/SectionIcons/bookmark.svg',
        order: 0,
    )]
    #[Input\Text(
        instructions: 'Field label used to describe the field',
        order: 1,
        placeholder: 'My Field',
    )]
    #[Middleware('injectInto', [
        'target' => 'handle',
        'camelize' => true,
        'bypassConditions' => [['name' => 'id', 'isTrue' => true]],
    ])]
    #[Validators\Required]
    protected string $label = '';

    #[Section('general')]
    #[Input\Text(
        instructions: "How you'll refer to this field in templates",
        order: 2,
        placeholder: 'myField',
    )]
    #[Middleware('handle')]
    #[Flag('code')]
    #[Limitation('layout.fields.handles')]
    #[Validators\Required]
    #[Validators\Handle]
    #[Validators\Length(100)]
    #[Validators\ReservedWord]
    protected string $handle = '';

    #[Translatable]
    #[Section('general')]
    #[Input\TextArea(
        instructions: 'Field specific user instructions',
        order: 3,
    )]
    protected string $instructions = '';

    #[Section('general')]
    #[Input\Boolean(
        label: 'Require this field',
        order: 5
    )]
    protected bool $required = false;

    #[Translatable]
    #[Section('general')]
    #[VisibilityFilter('properties.required')]
    #[Input\Text(
        label: 'Custom Validation Error',
        instructions: 'If this field is left empty upon submit, show this error message.',
        order: 5,
        placeholder: 'This field is required',
    )]
    protected ?string $requiredMessage = '';

    #[Section(
        handle: 'attributes',
        label: 'Attributes',
        icon: __DIR__.'/SectionIcons/list.svg',
        order: 999,
    )]
    #[Limitation('layout.fields.attributes')]
    #[ValueTransformer(FieldAttributesTransformer::class)]
    #[Input\Attributes(
        instructions: 'Add attributes to your field elements.',
        tabs: [
            [
                'handle' => 'container',
                'label' => 'Container',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'input',
                'label' => 'Input',
                'previewTag' => 'input',
            ],
            [
                'handle' => 'label',
                'label' => 'Label',
                'previewTag' => 'label',
            ],
            [
                'handle' => 'instructions',
                'label' => 'Instructions',
                'previewTag' => 'div',
            ],
            [
                'handle' => 'error',
                'label' => 'Error',
                'previewTag' => 'ul',
            ],
        ]
    )]
    protected FieldAttributesCollection $attributes;

    #[Section(
        handle: 'advanced',
        label: 'Advanced',
        icon: __DIR__.'/SectionIcons/advanced.svg',
        order: 1000,
    )]
    #[Limitation('layout.fields.types')]
    #[Input\FieldType(
        label: 'Field Type',
        instructions: 'Change the type of this field.',
        order: 2,
    )]
    protected ?string $fieldType = null;

    protected Parameters $parameters;

    protected ?int $id = null;
    protected ?string $uid = null;
    protected ?int $rowId = null;
    protected ?string $rowUid = null;
    protected ?int $order = null;
    protected int $pageIndex = 0;
    protected array $errors = [];

    /** @var T */
    protected mixed $value = null;

    /** @var T */
    private mixed $defaultValue = null;
    private bool $validated = false;

    private ?FieldAttributesCollection $compiledAttributes = null;

    public function __construct(
        #[Ignore]
        private Form $form
    ) {
        $this->attributes = new FieldAttributesCollection();
        $this->parameters = new Parameters();
    }

    public function __toString(): string
    {
        return $this->getValueAsString();
    }

    /**
     * @return T
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @param T $value
     */
    public function setValue(mixed $value): FieldInterface
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Render the complete set of HTML for this field
     * That includes the Label, Input and Error messages.
     */
    final public function render(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);

        Event::trigger($this, self::EVENT_RENDER_CONTAINER, new FieldRenderEvent($this));

        $containerAttributes = $this->getAttributes()->getContainer();
        $output = '<div'.$containerAttributes.'>';

        if (!$this instanceof InputOnlyInterface) {
            $output .= $this->renderLabel();
        }

        $instructionsBelow = 'below' === strtolower($this->parameters->instructions);

        // Show instructions above by default
        if (!$instructionsBelow) {
            $output .= $this->getInstructionsHtml();
        }

        $output .= $this->onBeforeInputHtml();
        $output .= $this->renderInput();
        $output .= $this->onAfterInputHtml();

        // Show instructions below only if set by a property
        if ($instructionsBelow) {
            $output .= $this->renderInstructions();
        }

        if ($this->getErrors()) {
            $output .= $this->renderErrors();
        }

        $output .= '</div>';

        return $this->renderRaw($output);
    }

    final public function renderContainerOpeningTag(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);
        Event::trigger($this, self::EVENT_RENDER_CONTAINER, new FieldRenderEvent($this));

        return $this->renderRaw($this->getContainerOpeningTagHtml());
    }

    final public function renderContainerClosingTag(): Markup
    {
        return $this->renderRaw($this->getContainerClosingTagHtml());
    }

    /**
     * Render the Label HTML.
     */
    final public function renderLabel(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);
        Event::trigger($this, self::EVENT_RENDER_LABEL, new FieldRenderEvent($this));

        return $this->renderRaw($this->getLabelHtml());
    }

    public function renderInstructions(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);
        Event::trigger($this, self::EVENT_RENDER_INSTRUCTIONS, new FieldRenderEvent($this));

        return $this->renderRaw($this->getInstructionsHtml());
    }

    /**
     * Render the Input HTML.
     */
    final public function renderInput(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);
        Event::trigger($this, self::EVENT_RENDER_INPUT, new FieldRenderEvent($this));

        return $this->renderRaw($this->getInputHtml());
    }

    /**
     * Outputs the HTML of errors.
     */
    final public function renderErrors(?array $parameters = null): Markup
    {
        $this->setParameters($parameters);
        Event::trigger($this, self::EVENT_RENDER_ERRORS, new FieldRenderEvent($this));

        return $this->renderRaw($this->getErrorHtml());
    }

    public function canRender(): bool
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
        return empty($this->errors);
    }

    /**
     * Returns an array of error messages.
     */
    public function getErrors(): array
    {
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
    public function addErrors(?array $errors = null): self
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
    public function addError(...$error): self
    {
        $this->addErrors($error);

        return $this;
    }

    /**
     * Return the field TYPE.
     */
    abstract public function getType(): string;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function getRowId(): ?int
    {
        return $this->rowId;
    }

    public function getRowUid(): ?string
    {
        return $this->rowUid;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function getNormalizeIdentificator(): null|int|string
    {
        return $this->getUid();
    }

    public function getHandle(): ?string
    {
        return $this->handle;
    }

    public function getLabel(): string
    {
        return $this->translate('label', $this->label);
    }

    public function getInstructions(): string
    {
        return $this->translate('instructions', $this->instructions);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getRequiredErrorMessage(): ?string
    {
        return $this->translate('requiredMessage', $this->requiredMessage);
    }

    public function getAttributes(): FieldAttributesCollection
    {
        $event = new CompileFieldAttributesEvent(
            $this,
            $this->attributes->clone(),
            FieldAttributesCollection::class
        );

        Event::trigger($this, self::EVENT_COMPILE_ATTRIBUTES, $event);

        return $event->getAttributes();
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
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
    public function getValueAsString(): string
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
        $attribute = \sprintf('form-input-%s', $this->getHandle());

        if ($this->parameters->id) {
            $attribute = $this->parameters->id;
        }

        $fieldIdPrefix = $this->getForm()->getProperties()->get('fieldIdPrefix', '');

        return $fieldIdPrefix.$attribute;
    }

    public function getContentGqlHandle(): ?string
    {
        return $this->getHandle();
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

    public function getContentGqlType(): array|Type
    {
        return Type::string();
    }

    public function getContentGqlMutationArgumentType(): array|Type
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

    public function setParameters(?array $parameters = null): void
    {
        if (!\is_array($parameters)) {
            return;
        }

        $event = new SetParametersEvent($this, $parameters);
        Event::trigger($this, self::EVENT_SET_PARAMETERS, $event);

        foreach ($event->getParameters() as $key => $value) {
            $this->parameters->add($key, $value);
        }
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    /**
     * Validate the field and add error messages if any.
     */
    public function validate(Form $form): void
    {
        if ($this->validated) {
            throw new FieldException('Field has been validated already');
        }

        $this->validated = true;

        $event = new ValidateEvent($form, $this);
        Event::trigger($this, self::EVENT_VALIDATE, $event);

        if (!$event->isValid) {
            $this->errors = [];
        }
    }

    public function implements(string ...$interfaces): bool
    {
        static $provider;
        if (null === $provider) {
            $provider = new ImplementationProvider();
        }

        $implementations = $provider->getImplementations($this::class);

        foreach ($interfaces as $interface) {
            if (\in_array($interface, $implementations, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated No longer used. Rules are automatically added to the form html as a separate dataset.
     */
    public function getRulesHtmlData(): string
    {
        return '';
    }

    protected function getContainerOpeningTagHtml(): string
    {
        return '<div'.$this->getAttributes()->getContainer().'>';
    }

    protected function getContainerClosingTagHtml(): string
    {
        return '</div>';
    }

    /**
     * Assemble the Label HTML string.
     */
    protected function getLabelHtml(): string
    {
        $attributes = $this->getAttributes()
            ->getLabel()
            ->clone()
            ->replace('for', $this->getIdAttribute())
        ;

        $output = '<label'.$attributes.'>';
        $output .= $this->getLabel();
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

        $output = '<div'.$this->getAttributes()->getInstructions().'>';
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

        $attributes = $this->getAttributes()
            ->getError()
            ->clone()
            ->setIfEmpty('class', 'errors')
        ;

        $output = '<ul'.$attributes.'>';

        foreach ($errors as $error) {
            if (\is_array($error)) {
                $error = implode(', ', $error);
            }

            $output .= '<li>'.htmlentities($error).'</li>';
        }

        $output .= '</ul>';

        return $output;
    }

    protected function getRequiredAttribute(): string
    {
        $attribute = '';

        if ($this->isRequired()) {
            $attribute = ' data-required';

            if ($this->parameters->useRequiredAttribute) {
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

    protected function translate(?string $handle, ?string $defaultValue = null): string
    {
        return Freeform::getInstance()->translations->getTranslation(
            $this->getForm(),
            TranslationsService::TYPE_FIELDS,
            $this->getUid(),
            $handle,
            $defaultValue
        );
    }

    protected function translateOption(?string $handle, string $key, string $defaultValue): string
    {
        $translation = Freeform::getInstance()->translations->getTranslation(
            $this->getForm(),
            TranslationsService::TYPE_FIELDS,
            $this->getUid(),
            $handle,
            '',
        );

        if (!$translation || !isset($translation['options'])) {
            return $defaultValue;
        }

        foreach ($translation['options'] as $option) {
            if ($option['value'] === $key) {
                return $option['label'];
            }
        }

        return $defaultValue;
    }

    protected function renderRaw(string $output): Markup
    {
        return Template::raw($output);
    }
}
