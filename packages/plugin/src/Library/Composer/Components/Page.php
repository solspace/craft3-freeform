<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Composer\Components;

use Solspace\Freeform\Fields\Pro\PasswordField;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\RememberPostedValueInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\StaticValueInterface;
use Solspace\Freeform\Library\Exceptions\FreeformException;

class Page implements \JsonSerializable, \Iterator, \ArrayAccess
{
    /** @var int */
    private $index;

    /** @var string */
    private $label;

    /** @var Row[] */
    private $rows;

    /** @var FieldInterface[] */
    private $fields;

    /**
     * Page constructor.
     *
     * @param int              $index
     * @param string           $label
     * @param Row[]            $rows
     * @param FieldInterface[] $fields
     */
    public function __construct($index, $label, array $rows, array $fields)
    {
        $this->index = (int) $index;
        $this->label = $label;
        $this->rows = $rows;
        $this->fields = $fields;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @return Row[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    /**
     * @template T
     *
     * @param null|class-string<T> $implements
     *
     * @return FieldInterface[]
     */
    public function getFields(string $implements = null): array
    {
        if (null !== $implements) {
            return array_filter($this->fields, function (AbstractField $field) use ($implements) {
                return $field instanceof $implements;
            });
        }

        return $this->fields;
    }

    public function getStorableFieldValues(): array
    {
        $submittedValues = [];

        foreach ($this->getFields() as $field) {
            if (
                $field instanceof NoStorageInterface
                && !$field instanceof RememberPostedValueInterface
                && !$field instanceof PasswordField
            ) {
                continue;
            }

            $value = null;
            if (!$field->isHidden()) {
                $value = $field->getValue();
                if ($field instanceof StaticValueInterface && !empty($value)) {
                    $value = $field->getStaticValue();
                }
            }

            $submittedValues[$field->getHandle()] = $value;
        }

        return $submittedValues;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return $this->rows;
    }

    /**
     * Return the current element.
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->rows);
    }

    /**
     * Move forward to next element.
     */
    public function next()
    {
        next($this->rows);
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return null !== $this->key() && false !== $this->key();
    }

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        reset($this->rows);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->rows[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->rows[$offset] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new FreeformException('Form Page ArrayAccess does not allow for setting values');
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        throw new FreeformException('Form Page ArrayAccess does not allow unsetting values');
    }
}
