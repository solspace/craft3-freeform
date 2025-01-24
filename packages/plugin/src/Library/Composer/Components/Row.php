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

use Solspace\Freeform\Library\Exceptions\FreeformException;

class Row implements \JsonSerializable, \Iterator, \ArrayAccess, \Countable
{
    /** @var string */
    private $id;

    /** @var AbstractField[] */
    private $fields;

    /**
     * @param string $id
     */
    public function __construct($id, array $fields)
    {
        $this->id = $id;
        $this->fields = $fields;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'columns' => $this->fields,
        ];
    }

    /**
     * Return the current element.
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->fields);
    }

    /**
     * Move forward to next element.
     */
    public function next()
    {
        next($this->fields);
    }

    /**
     * Return the key of the current element.
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->fields);
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
        reset($this->fields);
    }

    /**
     * Count elements of an object.
     *
     * @return int
     */
    public function count()
    {
        return \count($this->fields);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($offset)
    {
        return isset($this->fields[$offset]);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->fields[$offset] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($offset, $value)
    {
        throw new FreeformException('Form Page Row ArrayAccess does not allow unsetting values');
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($offset)
    {
        throw new FreeformException('Form Page Row ArrayAccess does not allow unsetting values');
    }
}
