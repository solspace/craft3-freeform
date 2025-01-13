<?php

namespace Solspace\Freeform\Bundles\Export\Objects;

class ColumnIterator implements \Iterator
{
    private int $position = 0;

    /** @var Row */
    private $columnCollection;

    /**
     * ColumnIterator constructor.
     */
    public function __construct(Row $columnCollection)
    {
        $this->columnCollection = $columnCollection;
    }

    public function current(): Column
    {
        return $this->columnCollection->getColumn($this->position);
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return null !== $this->columnCollection->getColumn($this->position);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }
}
