<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor\EventListeners;

use craft\mail\Message;
use Solspace\Freeform\Events\Mailer\SendEmailEvent;
use Solspace\Freeform\Events\Notifications\PrepareSendNotificationEvent;
use Solspace\Freeform\Integrations\Other\FormMonitor\Providers\FormMonitorProvider;
use Solspace\Freeform\Jobs\SendNotificationsJob;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Services\FormsService;
use Solspace\Freeform\Services\MailerService;
use yii\base\Event;

class EmailOverrides extends FeatureBundle
{
    public function __construct(
        private FormMonitorProvider $formMonitorProvider,
        private FormsService $formsService,
    ) {
        Event::on(
            SendNotificationsJob::class,
            SendNotificationsJob::EVENT_PREPARE_NOTIFICATION_JOB,
            [$this, 'handleJobPreparation'],
        );

        Event::on(
            MailerService::class,
            MailerService::EVENT_BEFORE_SEND,
            [$this, 'handleEmails'],
        );
    }

    public function handleJobPreparation(PrepareSendNotificationEvent $event): void
    {
        $job = $event->getJob();
        $form = $this->formsService->getFormById($job->formId);
        $isFormMonitorRequest = $this->formMonitorProvider->isRequestFromFormMonitor($form);
        if (!$isFormMonitorRequest) {
            return;
        }

        $job->headers = [
            'X-Form-Monitor' => 'true',
            'X-Form-Monitor-Form-Id' => $form->getId(),
            'X-Form-Monitor-Submission-Id' => $job->submissionId,
            'X-Form-Monitor-Request-Id' => $this->formMonitorProvider->getRequestId($form),
            'X-Form-Monitor-Notification-Type' => $job->notificationType,
        ];
    }

    public function handleEmails(SendEmailEvent $event): void
    {
        $message = $event->getMessage();
        $isFormMonitorRequest = $message->getHeader('X-Form-Monitor');
        if (!$isFormMonitorRequest) {
            return;
        }

        $to = $message->getTo();
        $replyTo = $message->getReplyTo();
        $cc = $message->getCc();
        $bcc = $message->getBcc();

        $message->setTo('test@formmonitor.com');
        $message->setCc([]);
        $message->setBcc([]);
        $message->setReplyTo([]);

        $this->setHeader($message, 'X-Form-Monitor-To', $to);
        $this->setHeader($message, 'X-Form-Monitor-Reply-To', $replyTo);
        $this->setHeader($message, 'X-Form-Monitor-Cc', $cc);
        $this->setHeader($message, 'X-Form-Monitor-Bcc', $bcc);
    }

    private function setHeader(Message $message, $header, mixed $value): void
    {
        if (!$value) {
            return;
        }

        if (\is_array($value)) {
            $recipients = [];
            foreach ($value as $email => $name) {
                if (empty($email)) {
                    continue;
                }

                $recipient = $email;
                if (!empty($name)) {
                    $recipient = $name.' <'.$email.'>';
                }

                $recipients[] = $recipient;
            }

            $message->setHeader($header, $recipients);
        } else {
            $value = (string) $value;
            $message->setHeader($header, $value);
        }
    }
}
