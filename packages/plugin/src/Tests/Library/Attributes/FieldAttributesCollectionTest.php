<?php

namespace Solspace\Freeform\Tests\Library\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Attributes\FieldAttributesCollection;

#[CoversClass(FieldAttributesCollection::class)]
class FieldAttributesCollectionTest extends TestCase
{
    public function testBuildFromArray()
    {
        $collection = new FieldAttributesCollection();
        $collection->getLabel()->set('class', 'class-1 and class-2');
        $collection->getInput()
            ->set('class', 'class-3 and class-4')
            ->set('data-test', 'test')
        ;

        $collection->merge([
            'class' => 'test',
            'input' => [
                'class' => 'input-class',
                'something' => 'else',
            ],
            'label' => [
                '=class' => 'label-class',
            ],
            'instructions' => [
                'class' => 'instructions-class',
            ],
        ]);

        $result = $collection->jsonSerialize();

        $this->assertEquals(
            (object) [
                'input' => (object) [
                    'class' => 'class-3 and class-4 input-class',
                    'data-test' => 'test',
                    'something' => 'else',
                ],
                'label' => (object) [
                    'class' => 'label-class',
                ],
                'instructions' => (object) [
                    'class' => 'instructions-class',
                ],
                'container' => (object) [],
                'error' => (object) [],
                'class' => 'test',
            ],
            $result
        );
    }
}
