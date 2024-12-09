<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\Jobs;

use craft\helpers\Db;
use craft\queue\BaseJob;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Freeform;

class FormMonitorCleanupJob extends BaseJob
{
    public function execute($queue): void
    {
        $date = new \DateTime('-1 days');
        $date->setTimezone(new \DateTimeZone('UTC'));

        $query = Submission::find()
            ->isHidden(true)
            ->andWhere(
                Db::parseDateParam(
                    Db::rawTableShortName(Submission::TABLE.'.[[dateCreated]]'),
                    $date,
                    '<',
                )
            )
            ->andWhere(['is not', Submission::TABLE.'.[[requestId]]', null])
        ;

        foreach ($query->batch() as $submissions) {
            foreach ($submissions as $submission) {
                \Craft::$app->elements->deleteElement($submission, true);
            }
        }
    }

    protected function defaultDescription(): ?string
    {
        return Freeform::t('Freeform: Clean-up Form Monitor data');
    }
}
