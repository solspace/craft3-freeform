<?php

namespace Solspace\Freeform\Jobs;

use craft\queue\BaseJob;
use Solspace\Freeform\Freeform;

class PurgeSubmissionsJob extends BaseJob
{
    public $age;

    public function execute($queue): void
    {
        Freeform::getInstance()->submissions->purgeSubmissions($this->age);
    }

    protected function defaultDescription(): ?string
    {
        return Freeform::t('Freeform: Purging Old Submissions');
    }
}
