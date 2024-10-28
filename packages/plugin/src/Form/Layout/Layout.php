<?php

namespace Solspace\Freeform\Form\Layout;

use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Library\Collections\FieldCollection;
use Solspace\Freeform\Library\Collections\RowCollection;

/**
 * @implements \IteratorAggregate<int, Row>
 */
class Layout implements \IteratorAggregate
{
    private RowCollection $rowCollection;
    private RowCollection $allRowsCollection;
    private FieldCollection $fieldCollection;

    public function __construct(private ?string $uid = null)
    {
        $this->rowCollection = new RowCollection();
        $this->allRowsCollection = new RowCollection();
        $this->fieldCollection = new FieldCollection();
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function getRows(): RowCollection
    {
        return $this->rowCollection;
    }

    public function getAllRows(): RowCollection
    {
        return $this->allRowsCollection;
    }

    public function hasFields(string $implements): bool
    {
        return !empty($this->getFields($implements));
    }

    public function getField(int|string $identificator): ?FieldInterface
    {
        return $this->fieldCollection->get($identificator);
    }

    public function getFields(?string $implements = null): FieldCollection
    {
        if (null !== $implements) {
            return $this->fieldCollection->getList($implements);
        }

        return $this->fieldCollection;
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->rowCollection->getIterator();
    }
}
