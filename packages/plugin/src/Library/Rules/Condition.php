<?php

namespace Solspace\Freeform\Library\Rules;

use Solspace\Freeform\Fields\FieldInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

class Condition
{
    public const TYPE_EQUALS = 'equals';
    public const TYPE_NOT_EQUALS = 'notEquals';
    public const TYPE_GREATER_THAN = 'greaterThan';
    public const TYPE_GREATER_THAN_OR_EQUALS = 'greaterThanOrEquals';
    public const TYPE_LESS_THAN = 'lessThan';
    public const TYPE_LESS_THAN_OR_EQUALS = 'lessThanOrEquals';
    public const TYPE_CONTAINS = 'contains';
    public const TYPE_NOT_CONTAINS = 'notContains';
    public const TYPE_STARTS_WITH = 'startsWith';
    public const TYPE_ENDS_WITH = 'endsWith';
    public const TYPE_IS_EMPTY = 'isEmpty';
    public const TYPE_IS_NOT_EMPTY = 'isNotEmpty';
    public const TYPE_IS_ONE_OF = 'isOneOf';
    public const TYPE_IS_NOT_ONE_OF = 'isNotOneOf';

    public function __construct(
        private string $uid,
        private FieldInterface $field,
        private string $operator,
        private string $value
    ) {}

    #[Groups(['builder'])]
    public function getUid(): string
    {
        return $this->uid;
    }

    public function getField(): FieldInterface
    {
        return $this->field;
    }

    #[Groups(['builder'])]
    #[SerializedName('field')]
    public function getFieldUid(): string
    {
        return $this->field->getUid();
    }

    #[Groups(['front-end'])]
    #[SerializedName('field')]
    public function getFieldHandle(): string
    {
        return $this->field->getHandle();
    }

    #[Groups(['front-end', 'builder'])]
    public function getOperator(): string
    {
        return $this->operator;
    }

    #[Groups(['front-end', 'builder'])]
    public function getValue(): array|string
    {
        return $this->value;
    }
}
