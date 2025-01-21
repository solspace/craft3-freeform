<?php

namespace Solspace\Freeform\Tests\Attributes\Property\Implementations\Options;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;

#[CoversClass(OptionCollection::class)]
class OptionCollectionTest extends TestCase
{
    public function testNestedChildren()
    {
        $collection = new OptionCollection();
        $collection->add('one', 'One');
        $collection->add('two', 'Two');
        $collection->addCollection(
            (new OptionCollection('Nested Collection'))
                ->add('nested_one', 'Nested One')
                ->add('nested_two', 'Nested Two')
        );
        $collection->addCollection(
            (new OptionCollection('Nested Second Collection'))
                ->add('nested2_one', 'Nested 2 One')
                ->add('nested2_two', 'Nested 2 Two')
                ->addCollection(
                    (new OptionCollection('Sub Nesting'))
                        ->add('sub_nesting', 'Sub Nesting'),
                )
        );

        $this->assertEquals(
            [
                ['value' => 'one', 'label' => 'One'],
                ['value' => 'two', 'label' => 'Two'],
                [
                    'label' => 'Nested Collection',
                    'children' => [
                        ['value' => 'nested_one', 'label' => 'Nested One'],
                        ['value' => 'nested_two', 'label' => 'Nested Two'],
                    ],
                ],
                [
                    'label' => 'Nested Second Collection',
                    'children' => [
                        ['value' => 'nested2_one', 'label' => 'Nested 2 One'],
                        ['value' => 'nested2_two', 'label' => 'Nested 2 Two'],
                        [
                            'label' => 'Sub Nesting',
                            'children' => [
                                ['value' => 'sub_nesting', 'label' => 'Sub Nesting'],
                            ],
                        ],
                    ],
                ],
            ],
            $collection->toArray()
        );
    }
}
