<?php

namespace Solspace\Freeform\Library\Collections;

/**
 * @template T
 */
abstract class Collection implements \IteratorAggregate, \ArrayAccess, \Countable, \JsonSerializable
{
    /** @var T[] */
    protected array $items;

    /** @var null|callable */
    protected $keySelector;

    /**
     * @param T[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * @param T $item
     */
    public function add($item, mixed $key = null): self
    {
        $this->validate($item);

        if (null === $key && \is_callable($this->keySelector)) {
            $key = ($this->keySelector)($item);
        }

        if (null !== $key) {
            $this->items[$key] = $item;
        } else {
            $this->items[] = $item;
        }

        return $this;
    }

    public function filter(callable $callback): self
    {
        return new static(array_filter($this->items, $callback));
    }

    /**
     * @return T
     */
    public function get(mixed $key, mixed $defaultValue = null): mixed
    {
        return $this->items[$key] ?? $defaultValue;
    }

    public function has(mixed $key): bool
    {
        return isset($this->items[$key]);
    }

    /**
     * @return T[]
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return \ArrayIterator<int, T>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    /**
     * @param T $value
     */
    public function offsetSet(mixed $offset, $value): void
    {
        $this->validate($value);

        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return \count($this->items);
    }

    public function jsonSerialize(): array
    {
        return $this->items;
    }

    protected static function supports(): array
    {
        return [];
    }

    private function validate(mixed $object): void
    {
        $implementations = $this->supports();
        if (empty($implementations)) {
            return;
        }

        foreach ($implementations as $implementation) {
            if ($object instanceof $implementation) {
                return;
            }
        }

        throw new \InvalidArgumentException(
            \sprintf(
                'Invalid item type "%s". Valid implementations are: %s',
                $object::class,
                implode(', ', static::supports())
            )
        );
    }
}
