<?php

namespace Solspace\Freeform\Jobs;

use craft\queue\BaseJob;
use Solspace\Freeform\Freeform;

class PurgeUnfinalizedAssetsJob extends BaseJob
{
    public int $age;

    public function execute($queue): void
    {
        Freeform::getInstance()->files->cleanUpUnfinalizedAssets($this->age);
    }

    protected function defaultDescription(): ?string
    {
        return Freeform::t('Freeform: Purging Unfinalized Assets');
    }
}
