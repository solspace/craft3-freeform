<?php

namespace Solspace\Freeform\Events\Notifications;

use craft\events\CancelableEvent;
use Solspace\Freeform\Jobs\SendNotificationsJob;

class PrepareSendNotificationEvent extends CancelableEvent
{
    public function __construct(private SendNotificationsJob $job)
    {
        parent::__construct();
    }

    public function getJob(): SendNotificationsJob
    {
        return $this->job;
    }
}
