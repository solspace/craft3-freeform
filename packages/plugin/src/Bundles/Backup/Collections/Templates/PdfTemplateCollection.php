<?php

namespace Solspace\Freeform\Bundles\Backup\Collections\Templates;

use Solspace\Freeform\Bundles\Backup\DTO\Templates\PdfTemplate;
use Solspace\Freeform\Library\Collections\Collection;

/**
 * @extends Collection<PdfTemplate>
 */
class PdfTemplateCollection extends Collection
{
    protected static function supports(): array
    {
        return [PdfTemplate::class];
    }
}
