<?php

namespace Solspace\Freeform\Library\Integrations\DataObjects;

use Solspace\Freeform\Library\Collections\Collection;

/**
 * @extends Collection<FieldObjectOption>
 */
class FieldOptionCollection extends Collection
{
    protected static function supports(): array
    {
        return [FieldObjectOption::class];
    }
}
