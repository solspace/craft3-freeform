<?php

namespace Solspace\Freeform\Bundles\Backup\Collections;

use Solspace\Freeform\Bundles\Backup\DTO\Translation;
use Solspace\Freeform\Library\Collections\Collection;

/**
 * @extends Collection<Translation>
 */
class TranslationCollection extends Collection
{
    protected static function supports(): array
    {
        return [Translation::class];
    }
}
