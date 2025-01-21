<?php

namespace Solspace\Freeform\Tests\Library\Collections;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Collections\Collection;

#[CoversClass(Collection::class)]
class CollectionTest extends TestCase
{
    public function testChecksForImplementation()
    {
        $collection = new class extends Collection {
            protected static function supports(): array
            {
                return [\JsonSerializable::class, \ArrayAccess::class];
            }
        };

        $first = new class implements \JsonSerializable {
            public string $test = 'test';

            public function jsonSerialize(): array
            {
                return ['variable' => $this->test];
            }
        };

        $collection->add($first);

        $second = new class implements \ArrayAccess {
            public function offsetExists(mixed $offset): bool
            {
                return true;
            }

            public function offsetGet(mixed $offset): string
            {
                return 'test';
            }

            public function offsetSet(mixed $offset, mixed $value): void {}

            public function offsetUnset(mixed $offset): void {}
        };

        $collection->add($second);

        $this->assertCount(2, $collection);
    }

    public function testThrowsOnInvalidType()
    {
        $object = new class {
            public string $test = 'test';
        };

        $collection = new class extends Collection {
            protected static function supports(): array
            {
                return [\JsonSerializable::class];
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Valid implementations are: JsonSerializable');

        $collection->add($object);
    }

    public function testFiltersOutItemsReturnsNewCollection()
    {
        $collection = new class extends Collection {};

        for ($i = 1; $i <= 10; ++$i) {
            $collection->add($i);
        }

        $this->assertCount(10, $collection);

        $filtered = $collection->filter(function ($item) {
            return 0 === $item % 2;
        });

        $this->assertCount(5, $filtered);
        $this->assertNotSame($collection, $filtered);
    }

    public function testKeySelector()
    {
        $collection = new class extends Collection {
            public function __construct(array $items = [])
            {
                parent::__construct($items);
                $this->keySelector = fn ($item) => $item->id;
            }
        };

        $collection->add((object) ['id' => 151, 'name' => 'One']);
        $collection->add((object) ['id' => 262, 'name' => 'Two']);
        $collection->add((object) ['id' => 373, 'name' => 'Three']);

        $this->assertCount(3, $collection);
        $this->assertSame('One', $collection->get(151)->name);
        $this->assertSame('Two', $collection->get(262)->name);
        $this->assertSame('Three', $collection->get(373)->name);
    }
}
