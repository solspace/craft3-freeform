<?php

namespace Solspace\Freeform\Services\Pro;

use Carbon\Carbon;
use craft\helpers\Db;
use craft\web\View;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use Solspace\Freeform\Records\FeedMessageRecord;
use Solspace\Freeform\Records\NotificationLogRecord;
use yii\base\Component;

class DigestService extends Component
{
    public const FREQUENCY_DAILY = -1;
    public const FREQUENCY_WEEKLY_SUNDAYS = 0;
    public const FREQUENCY_WEEKLY_MONDAYS = 1;
    public const FREQUENCY_WEEKLY_TUESDAYS = 2;
    public const FREQUENCY_WEEKLY_WEDNESDAYS = 3;
    public const FREQUENCY_WEEKLY_THURSDAYS = 4;
    public const FREQUENCY_WEEKLY_FRIDAYS = 5;
    public const FREQUENCY_WEEKLY_SATURDAYS = 6;

    public const CACHE_KEY_DIGEST = 'freeform-digest-cache-key';
    public const CACHE_TTL_DIGEST = 60 * 60 * 3; // every 3h

    public const TEMPLATE_PATH = '_templates/email/digest.twig';

    public function triggerDigest(Carbon $refDate): void
    {
        if (Freeform::getInstance()->edition()->isBelow(Freeform::EDITION_LITE)) {
            return;
        }

        $freeform = Freeform::getInstance();
        $settingsService = $freeform->settings;

        $isProduction = 'production' === strtolower(\Craft::$app->getConfig()->env);
        if (!$isProduction && $settingsService->isDigestOnlyOnProduction()) {
            return;
        }

        $devRecipients = $settingsService->getDigestRecipients();
        $devFrequency = $settingsService->getDigestFrequency();
        $this->parseDigest(
            NotificationLogRecord::TYPE_DIGEST_DEV,
            $devRecipients,
            $devFrequency,
            $refDate
        );

        $clientRecipients = $settingsService->getClientDigestRecipients();
        $clientFrequency = $settingsService->getClientDigestFrequency();
        $this->parseDigest(
            NotificationLogRecord::TYPE_DIGEST_CLIENT,
            $clientRecipients,
            $clientFrequency,
            $refDate
        );
    }

    public function sendDigest(RecipientCollection $recipients, string $type, Carbon $rangeStart, Carbon $rangeEnd): void
    {
        $isFullDigest = NotificationLogRecord::TYPE_DIGEST_DEV === $type;
        $mailer = Freeform::getInstance()->mailer;

        $templateMode = \Craft::$app->view->getTemplateMode();
        \Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $templatePath = \Craft::getAlias('@freeform/templates/'.self::TEMPLATE_PATH);
        $notification = NotificationTemplate::fromFile($templatePath);

        $recipients = $mailer->processRecipients($recipients);
        $message = $mailer->compileMessage(
            $notification,
            [
                'daily' => $rangeStart->isSameDay($rangeEnd),
                'fullDigest' => $isFullDigest,
                'rangeStart' => $rangeStart,
                'rangeEnd' => $rangeEnd,
                'forms' => $this->getFormData($rangeStart, $rangeEnd),
                'errors' => $this->getErrorData($rangeStart, $rangeEnd),
                'updates' => $this->getUpdateData(),
            ]
        );

        $message->setTo($recipients);

        \Craft::$app->mailer->send($message);
        \Craft::$app->view->setTemplateMode($templateMode);
    }

    private function parseDigest(string $type, RecipientCollection $recipients, int $frequency, Carbon $refDate): void
    {
        if (empty($recipients->emailsToArray())) {
            return;
        }

        $lookupStart = $refDate->clone()->startOfDay();
        $lookupEnd = $refDate->copy()->endOfDay();

        if (-1 !== $frequency && $lookupStart->dayOfWeek !== $frequency) {
            return;
        }

        $record = NotificationLogRecord::find()
            ->where(Db::parseDateParam('dateCreated', $lookupStart, '>='))
            ->andWhere(Db::parseDateParam('dateCreated', $lookupEnd, '<='))
            ->andWhere(['type' => $type])
            ->one()
        ;

        if ($record) {
            return;
        }

        if (-1 === $frequency) {
            $rangeStart = $lookupStart->copy()->subDay();
        } else {
            $rangeStart = $lookupStart->copy()->subWeek();
        }

        $rangeEnd = $lookupStart->copy()->subDay()->endOfDay();

        $this->sendDigest($recipients, $type, $rangeStart, $rangeEnd);

        $record = new NotificationLogRecord();
        $record->type = $type;
        $record->save();
    }

    private function getFormData(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $freeform = Freeform::getInstance();

        $formService = $freeform->forms;
        $forms = $formService->getAllForms();

        $submissions = $freeform->submissions->getSubmissionCountByForm(false, $rangeStart, $rangeEnd);
        $spam = $freeform->submissions->getSubmissionCountByForm(true, $rangeStart, $rangeEnd);

        $data = [];
        foreach ($forms as $form) {
            $data[] = [
                'form' => $form,
                'submissions' => $submissions[$form->getId()] ?? 0,
                'spam' => $spam[$form->getId()] ?? 0,
            ];
        }

        return $data;
    }

    private function getErrorData(Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $logReader = Freeform::getInstance()->logger->getLogReader();

        $data = [];
        foreach ($logReader->getLines(5) as $line) {
            $date = $line->getDate();
            if ($date && $rangeStart->lte($date) && $rangeEnd->gte($date)) {
                $data[] = $line;
            }
        }

        return $data;
    }

    private function getUpdateData(): array
    {
        $messages = FeedMessageRecord::find()
            ->where(['seen' => false])
            ->andWhere(['!=', 'type', 'new'])
            ->orderBy(['issueDate' => \SORT_DESC])
            ->all()
        ;

        $data = [];
        foreach ($messages as $message) {
            $data[] = $message->toArray();
        }

        return $data;
    }
}
