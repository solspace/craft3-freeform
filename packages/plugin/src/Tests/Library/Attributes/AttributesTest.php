<?php

namespace Solspace\Freeform\Tests\Library\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Attributes\Attributes;

#[CoversClass(Attributes::class)]
class AttributesTest extends TestCase
{
    public function testGathersAttributes()
    {
        $attributes = new Attributes();
        $attributes
            ->set('data-boolean', true)
            ->set('text', 'text value')
            ->set('number-value', 123)
            ->set('void')
            ->set('array-value', ['one', 'two', 'three'])
        ;

        $this->assertCount(5, $attributes);
        $this->assertEquals('text value', $attributes->get('text'));
    }

    public function testDoesNotShowFalseAttributes()
    {
        $attributes = new Attributes();
        $attributes->set('data-test-false', false);

        $this->assertEquals('', (string) $attributes);
    }

    public function testRendersEmptyStringAttributes()
    {
        $attributes = new Attributes();
        $attributes->set('data-empty-test', '');

        $this->assertEquals(' data-empty-test=""', (string) $attributes);
    }

    public function testBooleanValuesAddOnlyKey()
    {
        $attributes = new Attributes();
        $attributes->set('data-boolean-true', true);

        $this->assertEquals(' data-boolean-true', (string) $attributes);
    }

    public function testZeroNumericGeneratesAttribute()
    {
        $attributes = new Attributes();
        $attributes->set('data-numeric-zero', 0);

        $this->assertEquals(' data-numeric-zero="0"', (string) $attributes);
    }

    public function testEscapesHtml()
    {
        $attributes = new Attributes();
        $attributes->set('data-inject', '"><script>alert(\'hack!\');</script>');

        $this->assertEquals(
            ' data-inject="&quot;&gt;&lt;script&gt;alert(&#039;hack!&#039;);&lt;/script&gt;"',
            (string) $attributes
        );
    }

    public function testRendersKeysWithNoValue()
    {
        $attributes = new Attributes();
        $attributes->set('data-void');

        $this->assertEquals(' data-void', (string) $attributes);
    }

    public function testRendersWithNullKey()
    {
        $attributes = new Attributes();
        $attributes
            ->set('', 'empty-key')
            ->set('', 'other-empty-key')
        ;

        $this->assertEquals('', (string) $attributes);
    }

    public function testRendersObjects()
    {
        $attributes = new Attributes();
        $attributes->set('data-object', (object) ['one' => 1, 'two' => 2, 'three' => 3]);

        $this->assertEquals(' data-object="one:1 two:2 three:3"', (string) $attributes);
    }

    public function testMergeAdding()
    {
        $attributes = new Attributes();
        $attributes
            ->merge([
                'data-boolean' => true,
                'data-boolean-false' => false,
                'text' => 'text value',
                'empty-text' => '',
                'number-value' => 123,
                'void' => null,
                'array-value' => ['one', 'two', 'three'],
                'object-value' => (object) ['one' => 1, 'two' => 2, 'three' => 3],
            ])
        ;

        $this->assertEquals(
            ' data-boolean text="text value" empty-text="" number-value="123" void array-value="one two three" object-value="one:1 two:2 three:3"',
            (string) $attributes
        );
    }

    public function testMergeWithAttributeObject()
    {
        $mergeAttributes = new Attributes(['class' => 'test']);

        $attributes = new Attributes();
        $attributes->merge($mergeAttributes);

        $this->assertEquals(' class="test"', (string) $attributes);
    }

    public function testConstructorAdding()
    {
        $attributes = new Attributes([
            'data-boolean' => true,
            'data-boolean-false' => false,
            'text' => 'text value',
            'empty-text' => '',
            'number-value' => 123,
            'void' => null,
            'array-value' => ['one', 'two', 'three'],
            'object-value' => (object) ['one' => 1, 'two' => 2, 'three' => 3],
        ]);

        $this->assertEquals(
            ' data-boolean text="text value" empty-text="" number-value="123" void array-value="one two three" object-value="one:1 two:2 three:3"',
            (string) $attributes
        );
    }

    public function testSeveralAttributes()
    {
        $attributes = new Attributes();
        $attributes
            ->set('data-boolean', true)
            ->set('data-boolean-false', false)
            ->set('text', 'text value')
            ->set('empty-text', '')
            ->set('number-value', 123)
            ->set('void')
            ->set('array-value', ['one', 'two', 'three'])
            ->set('object-value', (object) ['one' => 1, 'two' => 2, 'three' => 3])
        ;

        $this->assertEquals(
            ' data-boolean text="text value" empty-text="" number-value="123" void array-value="one two three" object-value="one:1 two:2 three:3"',
            (string) $attributes
        );
    }

    public function testSetIfEmpty()
    {
        $attributes = new Attributes();
        $attributes
            ->set('text', 'text value')
            ->set('empty-text', '')
        ;

        $attributes->setIfEmpty('text', 'new text value');
        $attributes->setIfEmpty('non-existent', 'value');

        $this->assertEquals(
            ' text="text value" empty-text="" non-existent="value"',
            (string) $attributes
        );
    }

    public function testReplaceWithEqualSign()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('=class', 'replacer');
        $this->assertEquals(' class="replacer"', (string) $attributes);
    }

    public function testAppendWithPlus()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('+class', 'append-class');
        $this->assertEquals(' class="class-1 class-2 extra-class append-class"', (string) $attributes);
    }

    public function testRemoveNonExisting()
    {
        $attributes = new Attributes();

        $attributes->set('-class', 'extra-class');
        $this->assertEquals('', (string) $attributes);
    }

    public function testRemoveOneWithMinus()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('-class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('-class', 'non-existing-class');
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);
    }

    public function testRemoveSpacesAround()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', '   extra-class ');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('-class', '       extra-class   ');
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);
    }

    public function testRemoveSeveralWithString()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('-class', 'class-1 extra-class');
        $this->assertEquals(' class="class-2"', (string) $attributes);
    }

    public function testRemoveSeveralWithArray()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1', 'class-2']);
        $this->assertEquals(' class="class-1 class-2"', (string) $attributes);

        $attributes->set('class', 'extra-class');
        $this->assertEquals(' class="class-1 class-2 extra-class"', (string) $attributes);

        $attributes->set('-class', ['class-1', 'extra-class  ']);
        $this->assertEquals(' class="class-2"', (string) $attributes);
    }

    public function testAppendingArrayValues()
    {
        $attributes = new Attributes();
        $attributes->set('class', ['class-1 ', ' class-2']);
        $attributes->set('class', ['class-3 ', null, null, false, ' ', 'class-4']);

        $this->assertEquals(' class="class-1 class-2 class-3 class-4"', (string) $attributes);
    }

    public function testSettingSingleValueAsAppendToEmptyAttribute()
    {
        $attributes = new Attributes();
        $attributes->set('class', '');
        $attributes->set('class', 'class-1');

        $this->assertEquals(' class="class-1"', (string) $attributes);
    }
}
