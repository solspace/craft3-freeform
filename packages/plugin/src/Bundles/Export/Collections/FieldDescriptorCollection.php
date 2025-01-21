<?php

namespace Solspace\Freeform\Bundles\Export\Collections;

use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Library\Collections\Collection;

/**
 * @extends Collection<FieldDescriptor>
 */
class FieldDescriptorCollection extends Collection
{
    public function __construct(array $items = [])
    {
        parent::__construct($items);
        $this->keySelector = fn (FieldDescriptor $item) => $item->getId();
    }

    protected static function supports(): array
    {
        return [FieldDescriptor::class];
    }
}
