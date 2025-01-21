<?php

namespace Solspace\Freeform\Jobs;

use craft\queue\BaseJob;
use Solspace\Freeform\Freeform;

class PurgeSpamJob extends BaseJob
{
    public $age;

    public function execute($queue): void
    {
        Freeform::getInstance()->spamSubmissions->purgeSubmissions($this->age);
        $this->setProgress($queue, 1);
    }

    protected function defaultDescription(): ?string
    {
        return Freeform::t('Freeform: Purging Old Spam Submissions');
    }
}
