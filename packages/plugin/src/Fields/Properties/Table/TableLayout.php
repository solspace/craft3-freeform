<?php

namespace Solspace\Freeform\Fields\Properties\Table;

use Solspace\Freeform\Fields\Implementations\Pro\TableField;

/**
 * @implements \IteratorAggregate<int, TableColumn>
 * @implements \ArrayAccess<int, TableColumn>
 */
class TableLayout implements \IteratorAggregate, \ArrayAccess
{
    private array $rows = [];

    public function __construct(array $rows = [])
    {
        foreach ($rows as $column) {
            $this->add(
                $column['label'] ?? '',
                $column['value'] ?? '',
                $column['type'] ?? TableField::COLUMN_TYPE_STRING,
                $column['placeholder'] ?? '',
                $column['options'] ?? [],
                $column['checked'] ?? false,
                $column['required'] ?? false,
            );
        }
    }

    public function add(
        string $label,
        string $value,
        string $type,
        string $placeholder = '',
        array $options = [],
        bool $checked = false,
        bool $required = false,
    ): self {
        $column = new TableColumn();
        $column->label = $label;
        $column->value = $value;
        $column->type = $type;
        $column->placeholder = $placeholder;
        $column->options = $options;
        $column->checked = $checked;
        $column->required = $required;

        $this->rows[] = $column;

        return $this;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->rows);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->rows[$offset]);
    }

    public function offsetGet($offset): TableColumn
    {
        return $this->rows[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->rows[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->rows[$offset]);
    }

    public function count(): int
    {
        return \count($this->rows);
    }
}
