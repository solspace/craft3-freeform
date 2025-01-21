<?php

namespace Solspace\Freeform\Tests\Attributes\Property\PropertyTypes\Table;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Implementations\Table\TableTransformer;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;

#[CoversClass(TableTransformer::class)]
class TableTransformerTest extends TestCase
{
    public function testTransform()
    {
        $value = [
            ['label' => 'Col 1', 'value' => 'one', 'type' => 'text', 'required' => true],
            ['label' => 'Col 2', 'value' => 'two', 'type' => 'checkbox', 'checked' => true],
            ['label' => 'Col 3', 'value' => 'three', 'type' => 'select', 'options' => ['one', 'two', 'three']],
        ];

        $output = (new TableTransformer())->transform($value);

        $expected = new TableLayout();
        $expected
            ->add('Col 1', 'one', TableField::COLUMN_TYPE_STRING, required: true)
            ->add('Col 2', 'two', TableField::COLUMN_TYPE_CHECKBOX, checked: true)
            ->add('Col 3', 'three', TableField::COLUMN_TYPE_DROPDOWN, options: ['one', 'two', 'three'])
        ;

        $this->assertEquals($expected, $output);
    }

    public function testReverseTransform()
    {
        $value = new TableLayout();
        $value
            ->add(
                'Col 1',
                'one',
                TableField::COLUMN_TYPE_STRING,
                'Enter Text',
            )
            ->add(
                'Col 2',
                'two',
                TableField::COLUMN_TYPE_CHECKBOX,
                checked: true,
            )
            ->add(
                'Col 3',
                'three',
                TableField::COLUMN_TYPE_DROPDOWN,
                options: ['three', 'four', 'five'],
                required: true,
            )
        ;

        $output = (new TableTransformer())->reverseTransform($value);

        $expected = [
            [
                'label' => 'Col 1',
                'value' => 'one',
                'type' => 'text',
                'placeholder' => 'Enter Text',
                'options' => [],
                'checked' => false,
                'required' => false,
            ],
            [
                'label' => 'Col 2',
                'value' => 'two',
                'type' => 'checkbox',
                'placeholder' => '',
                'options' => [],
                'checked' => true,
                'required' => false,
            ],
            [
                'label' => 'Col 3',
                'value' => 'three',
                'type' => 'select',
                'placeholder' => '',
                'options' => ['three', 'four', 'five'],
                'checked' => false,
                'required' => true,
            ],
        ];

        $this->assertEquals($expected, $output);
    }
}
