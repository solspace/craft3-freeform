<?php

namespace Solspace\Freeform\Tests\Library\DataObjects\FieldType;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\PropertyCollection;

#[CoversClass(PropertyCollection::class)]
class PropertyCollectionTest extends TestCase
{
    public function testRemovesOneFlagged()
    {
        $prop1 = new Input\Text();
        $prop1->flags = [new Flag('test'), new Flag('flag')];

        $prop2 = new Input\Text();
        $prop2->flags = [new Flag('test'), new Flag('other')];

        $collection = new PropertyCollection();
        $collection
            ->add($prop1)
            ->add($prop2)
        ;

        $this->assertCount(2, $collection);

        $collection->removeFlagged('flag');

        $this->assertCount(1, $collection);
    }

    public function testRemovesMultipleFlagged()
    {
        $prop1 = new Input\Text();
        $prop1->flags = [new Flag('test'), new Flag('flag')];

        $prop2 = new Input\Text();
        $prop2->flags = [new Flag('test'), new Flag('other')];

        $collection = new PropertyCollection();
        $collection
            ->add($prop1)
            ->add($prop2)
        ;

        $this->assertCount(2, $collection);

        $collection->removeFlagged('test');

        $this->assertCount(0, $collection);
    }

    public function testRemovesVariousFlags()
    {
        $prop1 = new Input\Text();
        $prop1->flags = [new Flag('test'), new Flag('flag')];

        $prop2 = new Input\Text();
        $prop2->flags = [new Flag('test'), new Flag('other')];

        $collection = new PropertyCollection();
        $collection
            ->add($prop1)
            ->add($prop2)
        ;

        $this->assertCount(2, $collection);

        $collection->removeFlagged('flag', 'other');

        $this->assertCount(0, $collection);
    }
}
