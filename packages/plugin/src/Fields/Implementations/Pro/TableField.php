<?php

namespace Solspace\Freeform\Fields\Implementations\Pro;

use GraphQL\Type\Definition\Type as GQLType;
use Solspace\Freeform\Attributes\Field\Type;
use Solspace\Freeform\Attributes\Property\Implementations\Attributes\TableAttributesTransformer;
use Solspace\Freeform\Attributes\Property\Implementations\Table\TableTransformer;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Limitation;
use Solspace\Freeform\Attributes\Property\Section;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Events\Fields\CompileFieldAttributesEvent;
use Solspace\Freeform\Fields\AbstractField;
use Solspace\Freeform\Fields\Interfaces\EncryptionInterface;
use Solspace\Freeform\Fields\Interfaces\ExtraFieldInterface;
use Solspace\Freeform\Fields\Interfaces\MultiDimensionalValueInterface;
use Solspace\Freeform\Fields\Interfaces\MultiValueInterface;
use Solspace\Freeform\Fields\Interfaces\TableInterface;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;
use Solspace\Freeform\Fields\Traits\EncryptionTrait;
use Solspace\Freeform\Fields\Traits\MultipleValueTrait;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Attributes\Attributes;
use Solspace\Freeform\Library\Attributes\TableAttributesCollection;
use Symfony\Component\Serializer\Annotation\Ignore;
use yii\base\Event;

#[Type(
    name: 'Table',
    typeShorthand: 'table',
    iconPath: __DIR__.'/../Icons/table.svg',
    previewTemplatePath: __DIR__.'/../PreviewTemplates/table.ejs',
)]
class TableField extends AbstractField implements MultiValueInterface, MultiDimensionalValueInterface, ExtraFieldInterface, EncryptionInterface, TableInterface
{
    use EncryptionTrait;
    use MultipleValueTrait;

    public const EVENT_COMPILE_TABLE_ATTRIBUTES = 'compile-table-attributes';

    public const COLUMN_TYPE_STRING = 'text';
    public const COLUMN_TYPE_DROPDOWN = 'select';
    public const COLUMN_TYPE_CHECKBOX = 'checkbox';
    public const COLUMN_TYPE_RADIO = 'radio';
    public const COLUMN_TYPE_TEXTAREA = 'textarea';

    public array $columns = [];

    #[ValueTransformer(TableTransformer::class)]
    #[Input\Table(
        label: 'Table Layout',
        value: [],
        options: [
            [
                'value' => self::COLUMN_TYPE_STRING,
                'label' => 'Text',
            ],
            [
                'value' => self::COLUMN_TYPE_TEXTAREA,
                'label' => 'Textarea',
            ],
            [
                'value' => self::COLUMN_TYPE_CHECKBOX,
                'label' => 'Checkbox',
            ],
            [
                'value' => self::COLUMN_TYPE_RADIO,
                'label' => 'Radios',
            ],
            [
                'value' => self::COLUMN_TYPE_DROPDOWN,
                'label' => 'Dropdown',
            ],
        ],
    )]
    protected TableLayout $tableLayout;

    #[Input\Boolean('Use built-in javascript for adding and removing rows')]
    protected bool $useScript = true;

    #[Input\Integer(
        label: 'Maximum number of rows',
        instructions: 'Set the maximum number of rows that can be added to the table.',
    )]
    protected ?int $maxRows = null;

    #[Input\Text(
        label: 'Add Button Label',
        instructions: 'Set the label for the add button.',
    )]
    protected string $addButtonLabel = 'Add';

    #[Input\Text(
        label: 'Add Button Markup',
        instructions: 'Set the markup for the add button.',
    )]
    protected ?string $addButtonMarkup = null;

    #[Input\Text(
        label: 'Remove Button Label',
        instructions: 'Set the label for the remove button.',
    )]
    protected string $removeButtonLabel = 'Remove';

    #[Input\Text(
        label: 'Remove Button Markup',
        instructions: 'Set the markup for the remove button.',
    )]
    protected ?string $removeButtonMarkup = null;

    #[Section('attributes')]
    #[Limitation('layout.fields.attributes')]
    #[ValueTransformer(TableAttributesTransformer::class)]
    #[Input\Attributes(
        instructions: 'Add attributes to your field elements.',
        tabs: [
            [
                'handle' => 'table',
                'label' => 'Table',
                'previewTag' => 'table',
            ],
            [
                'handle' => 'row',
                'label' => 'Row',
                'previewTag' => 'tr',
            ],
            [
                'handle' => 'column',
                'label' => 'Column',
                'previewTag' => 'td',
            ],
            [
                'handle' => 'label',
                'label' => 'Label',
                'previewTag' => 'label',
            ],
            [
                'handle' => 'input',
                'label' => 'Input',
                'previewTag' => 'input',
            ],
            [
                'handle' => 'dropdown',
                'label' => 'Dropdown',
                'previewTag' => 'select',
            ],
            [
                'handle' => 'checkbox',
                'label' => 'Checkbox',
                'previewTag' => 'input',
            ],
            [
                'handle' => 'addButton',
                'label' => 'Add Button',
                'previewTag' => 'button',
            ],
            [
                'handle' => 'removeButton',
                'label' => 'Remove Button',
                'previewTag' => 'button',
            ],
        ]
    )]
    protected TableAttributesCollection $tableAttributes;

    public function __construct(#[Ignore] Form $form)
    {
        $this->tableAttributes = new TableAttributesCollection();

        parent::__construct($form);
    }

    public function getType(): string
    {
        return self::TYPE_TABLE;
    }

    public function getTableLayout(): TableLayout
    {
        return $this->tableLayout;
    }

    public function isUseScript(): bool
    {
        return $this->useScript;
    }

    public function getMaxRows(): ?int
    {
        return $this->maxRows;
    }

    public function getAddButtonLabel(): string
    {
        return $this->addButtonLabel;
    }

    public function getAddButtonMarkup(): ?string
    {
        return $this->addButtonMarkup;
    }

    public function getRemoveButtonLabel(): string
    {
        return $this->removeButtonLabel;
    }

    public function getRemoveButtonMarkup(): ?string
    {
        return $this->removeButtonMarkup;
    }

    public function getTableAttributes(): TableAttributesCollection
    {
        $event = new CompileFieldAttributesEvent(
            $this,
            $this->tableAttributes->clone(),
            TableAttributesCollection::class
        );

        Event::trigger($this, self::EVENT_COMPILE_ATTRIBUTES, $event);

        return $event->getAttributes();
    }

    public function setValue(mixed $value): self
    {
        $layout = $this->getTableLayout();

        $this->value = $values = [];
        if (!\is_array($value)) {
            return $this;
        }

        foreach ($value as $row) {
            if (!\is_array($row)) {
                continue;
            }

            $hasSingleValue = false;
            $rowValues = [];
            foreach ($layout as $index => $column) {
                $value = $row[$index] ?? '';
                if ($value) {
                    $hasSingleValue = true;
                }

                $rowValues[$index] = $value;
            }

            if (!$hasSingleValue) {
                continue;
            }

            $values[] = $rowValues;
        }

        $this->value = $values;

        return $this;
    }

    public function getContentGqlType(): array|GQLType
    {
        return GQLType::listOf(GQLType::listOf(GQLType::string()));
    }

    public function getContentGqlMutationArgumentType(): array|GQLType
    {
        $layout = [];
        $textValuesInclude = '';
        $dropdownValuesInclude = '';
        $checkboxValuesInclude = '';

        foreach ($this->getTableLayout() as $column) {
            $type = $column->type ?? self::COLUMN_TYPE_STRING;

            if (self::COLUMN_TYPE_DROPDOWN === $type) {
                $dropdownValues = [];
                $options = explode(';', $column->value);

                foreach ($options as $option) {
                    $dropdownValues[] = '"'.$option.'"';
                }

                if (!empty($dropdownValues)) {
                    $dropdownValuesInclude .= '- "'.$column->label.'" column:'."\n";
                    $dropdownValuesInclude .= '-- Single option value allowed.'."\n";
                    $dropdownValuesInclude .= '-- Options include '.implode(', ', $dropdownValues).'.';
                }

                $layout[] = '"'.$column->label.'"';
            } elseif (self::COLUMN_TYPE_CHECKBOX === $type) {
                $checkboxValuesInclude .= '- "'.$column->label.'" column:'."\n";
                $checkboxValuesInclude .= '-- Single option value allowed.'."\n";
                $checkboxValuesInclude .= '-- Option value is "'.$column->value.'".';

                $layout[] = '"'.$column->label.'"';
            } else {
                $textValuesInclude .= '- "'.$column->label.'" column:'."\n";
                $textValuesInclude .= '-- Single value allowed.'."\n";

                $layout[] = '"'.$column->label.'"';
            }
        }

        $description = [];
        $description[] = $this->getInstructions();
        $description[] = 'Expected layout [['.implode(', ', $layout).']].';
        $description[] = $textValuesInclude;
        $description[] = $dropdownValuesInclude;
        $description[] = $checkboxValuesInclude;
        $description = implode("\n", $description);

        return [
            'name' => $this->getContentGqlHandle(),
            'type' => $this->getContentGqlType(),
            'description' => trim($description),
        ];
    }

    protected function getInputHtml(): string
    {
        $layout = $this->getTableLayout();

        $handle = $this->getHandle();
        $values = $this->getValue();

        if (empty($values)) {
            $values = [];
            foreach ($layout as $column) {
                match ($column->type) {
                    self::COLUMN_TYPE_CHECKBOX => $values[] = null,
                    default => $values[] = $column->value,
                };
            }

            $values = [$values];
        }

        $attributes = $this->getTableAttributes();

        $tableAttributes = $attributes
            ->getTable()
            ->clone()
            ->replace('data-freeform-table')
        ;

        if ($this->isUseScript()) {
            $tableAttributes->set('data-scripts-enabled', true);
        }

        if ($this->getMaxRows()) {
            $tableAttributes->set('data-max-rows', $this->getMaxRows());
        }

        $rowAttributes = $attributes->getRow();

        $id = $this->getIdAttribute();
        $output = '<table'.$tableAttributes.'>';

        $output .= '<thead>';
        $output .= '<tr>';

        foreach ($layout as $column) {
            $label = $column->label;
            $defaultValue = $column->value;
            if (self::COLUMN_TYPE_CHECKBOX === $column->type) {
                $defaultValue = $column->checked ? '1' : '0';
            }

            $isRequired = $column->required;

            $thAttributes = new Attributes();
            $thAttributes
                ->merge($attributes->getLabel())
                ->set('data-default-value', $defaultValue)
                ->set('data-column-required', $isRequired)
                ->set(($isRequired ? '+' : '-').'class', 'freeform-required')
            ;

            $output .= '<th'.$thAttributes.'>'.htmlentities($label).'</th>';
        }
        $output .= '<th>&nbsp;</th></tr>';
        $output .= '</thead>';

        $columnAttributes = $attributes->getColumn();

        $output .= '<tbody>';
        foreach ($values as $rowIndex => $row) {
            $output .= '<tr'.$rowAttributes.'>';

            foreach ($layout as $index => $column) {
                $type = $column->type;
                $defaultValue = $column->value;
                $value = $row[$index] ?? $defaultValue;
                $value = htmlentities($value);

                $output .= '<td'.$columnAttributes.'>';

                $name = "{$handle}[{$rowIndex}][{$index}]";

                switch ($type) {
                    case self::COLUMN_TYPE_CHECKBOX:
                        $inputAttributes = $attributes
                            ->getCheckbox()
                            ->clone()
                            ->replace('type', 'checkbox')
                            ->replace('name', $name)
                            ->replace('value', '1')
                            ->replace('data-default-value', $column->checked ? '1' : '0')
                            ->replace('checked', $column->checked)
                        ;

                        $output .= '<input'.$inputAttributes.' />';

                        break;

                    case self::COLUMN_TYPE_DROPDOWN:
                        $dropdownAttributes = $attributes
                            ->getDropdown()
                            ->clone()
                            ->replace('data-default-value', $defaultValue)
                            ->replace('name', $name)
                        ;

                        $options = $column->options;
                        $output .= '<select'.$dropdownAttributes.'>';

                        foreach ($options as $option) {
                            $optionAttributes = (new Attributes())
                                ->set('value', $option)
                                ->set('selected', $option === $value)
                            ;

                            $output .= '<option '.$optionAttributes.'>'.$option.'</option>';
                        }

                        $output .= '</select>';

                        break;

                    case self::COLUMN_TYPE_RADIO:
                        $radioAttributes = $attributes
                            ->getRadios()
                            ->clone()
                            ->replace('type', 'radio')
                            ->replace('name', $name)
                        ;

                        $options = $column->options;
                        $output .= '<div>';

                        foreach ($options as $radioIndex => $option) {
                            $isChecked = $option === $value;
                            $radioId = 'labeled-'.$handle.'-'.$rowIndex.'-'.$index.'-'.$radioIndex;
                            $optionAttributes = $radioAttributes
                                ->clone()
                                ->replace('id', $radioId)
                                ->replace('value', $option)
                                ->replace('checked', $isChecked)
                                ->replace('data-default-value', $defaultValue)
                            ;

                            $output .= '<div>';
                            $output .= '<input'.$optionAttributes.' />';
                            $output .= '<label for="'.$radioId.'">'.$option.'</label>';
                            $output .= '</div>';
                        }

                        $output .= '</div>';

                        break;

                    case self::COLUMN_TYPE_TEXTAREA:
                        $inputAttributes = $attributes
                            ->getTextarea()
                            ->clone()
                            ->replace('type', 'text')
                            ->replace('name', $name)
                            ->replace('placeholder', $column->placeholder)
                            ->replace('data-default-value', $defaultValue)
                        ;

                        $output .= '<textarea'.$inputAttributes.'>'.$value.'</textarea>';

                        break;

                    case self::COLUMN_TYPE_STRING:
                    default:
                        $inputAttributes = $attributes
                            ->getInput()
                            ->clone()
                            ->replace('type', 'text')
                            ->replace('name', $name)
                            ->replace('value', $value)
                            ->replace('placeholder', $column->placeholder)
                            ->replace('data-default-value', $defaultValue)
                        ;

                        $output .= '<input'.$inputAttributes.' />';

                        break;
                }

                $output .= '</td>';
            }

            if ($this->getRemoveButtonMarkup()) {
                $output .= '<td'.$columnAttributes.'>';
                $output .= $this->getRemoveButtonMarkup();
                $output .= '</td>';
            } else {
                if ($this->isUseScript()) {
                    $output .= '<td'.$columnAttributes.'>';

                    $buttonAttributes = $attributes
                        ->getRemoveButton()
                        ->clone()
                        ->replace('data-freeform-table-remove-row')
                        ->setIfEmpty('type', 'button')
                    ;

                    $output .= '<button'.$buttonAttributes.'>'
                        .($this->getParameters()->removeButtonLabel ?? $this->getRemoveButtonLabel())
                        .'</button>';

                    $output .= '</td>';
                }
            }

            $output .= '</tr>';
        }
        $output .= '</tbody>';
        $output .= '</table>';

        if ($this->getAddButtonMarkup()) {
            $output .= $this->getAddButtonMarkup();
        } else {
            if ($this->isUseScript()) {
                $buttonAttributes = $attributes
                    ->getAddButton()
                    ->clone()
                    ->replace('data-freeform-table-add-row')
                    ->replace('data-target', $id)
                    ->setIfEmpty('type', 'button')
                ;

                $output .= '<button'.$buttonAttributes.'>'
                    .($this->getParameters()->addButtonLabel ?? $this->getAddButtonLabel())
                    .'</button>';
            }
        }

        return $output;
    }
}
