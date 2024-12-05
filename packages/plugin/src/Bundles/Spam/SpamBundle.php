<?php

namespace Solspace\Freeform\Bundles\Spam;

use Solspace\Freeform\Elements\SpamSubmission;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Forms\SubmitEvent;
use Solspace\Freeform\Events\Submissions\ProcessSubmissionEvent;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use yii\base\Event;

class SpamBundle extends FeatureBundle
{
    public function __construct()
    {
        Event::on(
            Form::class,
            Form::EVENT_SUBMIT,
            [$this, 'onFormSubmit']
        );

        Event::on(
            Submission::class,
            Submission::EVENT_PROCESS_SUBMISSION,
            [$this, 'processSpamSubmission']
        );
    }

    public static function getPriority(): int
    {
        return 800;
    }

    public function onFormSubmit(SubmitEvent $event): void
    {
        $form = $event->getForm();
        if (!$form->isMarkedAsSpam()) {
            return;
        }

        $isSpamFolderEnabled = $this->plugin()->settings->isSpamFolderEnabled();
        if ($isSpamFolderEnabled) {
            return;
        }

        $event->isValid = false;
    }

    public function processSpamSubmission(ProcessSubmissionEvent $event): void
    {
        // TODO: refactor due to mailing list field changes
        $submission = $event->getSubmission();

        if (!$submission instanceof SpamSubmission || !$submission->id || !$submission->isSpam) {
            return;
        }

        $this->plugin()->integrationsQueue->enqueueIntegrations($submission, []);

        // Prevent further processing of this submission
        $event->isValid = false;
    }
}
