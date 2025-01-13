<?php

namespace Solspace\Freeform\Bundles\Notifications\Export;

use Carbon\Carbon;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\web\Application;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Bundles\FeatureBundle;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Library\Exceptions\FreeformException;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;
use Solspace\Freeform\Records\NotificationLogRecord;
use Solspace\Freeform\Records\NotificationTemplateRecord;
use Solspace\Freeform\Records\Pro\ExportNotificationRecord;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use yii\base\Event;
use yii\base\Exception;
use yii\base\InvalidConfigException;

class ExportNotifications extends FeatureBundle
{
    public const CACHE_KEY = 'export-notifications';
    public const CACHE_TTL = 60 * 60 * 3; // every 3h

    public const NOTIFICATION_TYPE = 'export-notification';

    public function __construct()
    {
        Event::on(Application::class, Application::EVENT_AFTER_REQUEST, [$this, 'handleNotifications']);
    }

    /**
     * @throws InvalidConfigException
     * @throws FreeformException
     * @throws LoaderError
     * @throws SyntaxError
     * @throws Exception
     */
    public function handleNotifications(): void
    {
        if (Freeform::isLocked(self::CACHE_KEY, self::CACHE_TTL)) {
            return;
        }

        if (!\Craft::$app->db->tableExists(ExportNotificationRecord::TABLE)) {
            return;
        }

        $freeform = Freeform::getInstance();
        $mailer = $freeform->mailer;
        $exportService = $freeform->exportProfiles;

        /** @var ExportNotificationRecord[] $notifications */
        $notifications = ExportNotificationRecord::find()->all();

        foreach ($notifications as $notification) {
            if (!$this->checkLock($notification)) {
                continue;
            }

            $profile = $notification->getProfile();
            $form = $profile->getForm();

            $variables = [
                'form' => $form,
                'profile' => $profile,
                'date' => new Carbon(),
            ];

            $record = NotificationTemplateRecord::create();
            $record->id = 0;
            $record->uid = StringHelper::UUID();
            $record->name = 'Export Notification';
            $record->handle = 'export-notification';
            $record->fromName = \Craft::$app->projectConfig->get('email.fromName');
            $record->fromEmail = \Craft::$app->projectConfig->get('email.fromEmail');

            $record->subject = $mailer->renderString($notification->subject, $variables);

            $message = $mailer->renderString($notification->message, $variables);
            $record->bodyHtml = $message;
            $record->bodyText = $message;

            $template = NotificationTemplate::fromRecord($record);
            $recipients = RecipientCollection::fromArray(json_decode($notification->recipients));

            $message = $mailer->compileMessage($template, $variables);
            $message->setTo($mailer->processRecipients($recipients));

            $exporter = $exportService->createExporter(
                $notification->fileType,
                $form,
                $profile->getQuery(),
                $profile->getFieldDescriptors()
            );

            $fileName = $mailer->renderString(
                $notification->fileName ?? '',
                $variables
            );

            $exportFile = tmpfile();
            $exporter->export($exportFile);

            $message->attachContent(
                $exportFile,
                [
                    'fileName' => $fileName.'.'.$exporter->getFileExtension(),
                    'contentType' => $exporter->getMimeType(),
                ]
            );

            \Craft::$app->mailer->send($message);
        }
    }

    private function checkLock(ExportNotificationRecord $record): bool
    {
        if (empty($record->getRecipientArray())) {
            return false;
        }

        $frequency = (int) $record->frequency;
        $type = self::NOTIFICATION_TYPE.'-'.$record->id;

        $lookupStart = new Carbon('now');
        $lookupStart->setTime(0, 0, 0);

        $lookupEnd = $lookupStart->copy()->setTime(23, 59, 59);

        if (-1 !== $frequency && $lookupStart->dayOfWeek !== $frequency) {
            return false;
        }

        $record = NotificationLogRecord::find()
            ->where(Db::parseDateParam('dateCreated', $lookupStart, '>='))
            ->andWhere(Db::parseDateParam('dateCreated', $lookupEnd, '<='))
            ->andWhere(['type' => $type])
            ->one()
        ;

        if ($record) {
            return false;
        }

        $record = new NotificationLogRecord();
        $record->type = $type;
        $record->save();

        return true;
    }
}
