<?php

namespace Solspace\Freeform\Form\Layout;

use Solspace\Freeform\Form\Layout\Cell\Cell;
use Solspace\Freeform\Library\Collections\FieldCollection;

/**
 * @implements \IteratorAggregate<int, Cell>
 */
class Row implements \IteratorAggregate
{
    private ?int $id;
    private ?string $uid;
    private int $index;

    private FieldCollection $fieldCollection;
    private FieldCollection $allFieldsCollection;

    public function __construct(array $config = [])
    {
        $this->id = $config['id'] ?? null;
        $this->uid = $config['uid'] ?? null;
        $this->index = $config['index'] ?? 0;

        $this->fieldCollection = new FieldCollection();
        $this->allFieldsCollection = new FieldCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getFields(null|array|string $implements = null, ?string $strategy = null): FieldCollection
    {
        return $this->fieldCollection->getList($implements, $strategy);
    }

    public function getAllFields(null|array|string $implements = null, ?string $strategy = null): FieldCollection
    {
        return $this->allFieldsCollection->getList($implements, $strategy);
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->fieldCollection->getIterator();
    }
}
