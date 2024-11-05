<?php

namespace Solspace\Freeform\Integrations\Other\Jira;

use Solspace\Freeform\Bundles\Integrations\Providers\FormIntegrationsProvider;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Submissions\ProcessSubmissionEvent;
use Solspace\Freeform\Integrations\Other\Jira\Cards\JiraCards;
use Solspace\Freeform\Jobs\FreeformQueueHandler;
use Solspace\Freeform\Jobs\ProcessIntegrationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class JiraBundle extends FeatureBundle
{
    public function __construct(
        private FormIntegrationsProvider $integrationsProvider,
        private FreeformQueueHandler $queueHandler
    ) {
        Event::on(
            Submission::class,
            Submission::EVENT_PROCESS_SUBMISSION,
            [$this, 'pushToJiraCards']
        );
    }

    public function pushToJiraCards(ProcessSubmissionEvent $event): void
    {
        if (!$event->isValid) {
            return;
        }

        $form = $event->getForm();
        if (!$form->hasOptInPermission()) {
            return;
        }

        if ($form->isDisabled()->api) {
            return;
        }

        if (!$this->integrationsProvider->getForForm($form, JiraCards::class)) {
            return;
        }

        $this->queueHandler->executeIntegrationJob(
            new ProcessIntegrationsJob([
                'formId' => $form->getId(),
                'postedData' => $event->getSubmission()->getFormFieldValues(),
                'type' => JiraCards::class,
            ])
        );
    }
}
