<?php

namespace Solspace\Freeform\controllers\notifications;

use Solspace\Freeform\Bundles\Notifications\Providers\NotificationLoggerProvider;
use Solspace\Freeform\Controllers\BaseController;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Library\Helpers\StringHelper;
use Solspace\Freeform\Notifications\Components\Recipients\Recipient;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use yii\web\Response;

class SenderController extends BaseController
{
    public function actionDialogue(): Response
    {
        $templates = ['' => '---'];
        foreach ($this->getNotificationsService()->getAllNotifications() as $id => $notification) {
            $templates[$id] = $notification->name;
        }

        return $this->renderTemplate(
            'freeform/_components/modals/send_additional_notification',
            ['templates' => $templates]
        );
    }

    public function actionSend(): Response
    {
        $template = $this->request->post('template');
        if (!$template) {
            $this->response->statusCode = 400;

            return $this->asJson('Please select a template');
        }

        $emails = $this->request->post('emails');
        $emails = StringHelper::extractSeparatedValues($emails);
        if (!\is_array($emails) || empty($emails)) {
            $this->response->statusCode = 400;

            return $this->asJson('No emails specified');
        }

        $recipients = new RecipientCollection();
        foreach ($emails as $email) {
            $recipients->add(new Recipient($email, ''));
        }

        $submissionIds = $this->request->post('submissionIds', []);
        if (empty($submissionIds)) {
            return $this->asJson(true);
        }

        $loggerProvider = \Craft::$container->get(NotificationLoggerProvider::class);

        foreach ($submissionIds as $submissionId) {
            $submission = $this->getSubmissionsService()->getSubmissionById($submissionId);
            if (!$submission) {
                continue;
            }

            $form = $submission->getForm();
            $form->valuesFromSubmission($submission);

            $notification = Freeform::getInstance()
                ->notifications
                ->requireNotification(
                    $form,
                    $template,
                    'Send notification from CP Submissions Index page'
                )
            ;

            if (!$notification) {
                continue;
            }

            $notification = NotificationTemplate::fromRecord($notification);
            $logger = $loggerProvider->getLogger($notification, $form);

            $this->getMailerService()->sendEmail(
                $form,
                $recipients,
                $notification,
                $submission,
                $logger,
            );
        }

        return $this->asJson(true);
    }
}
