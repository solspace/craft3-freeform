<?php

namespace Solspace\Freeform\Fields\Properties\Table;

class TableColumn
{
    public string $label;
    public string $type;
    public string $value;
    public array $options = [];
    public string $placeholder = '';
    public bool $checked = false;
    public bool $required = false;
}
