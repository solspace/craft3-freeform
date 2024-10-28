<?php

namespace Solspace\Freeform\Services\Notifications;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Exceptions\DataObjects\EmailTemplateException;
use Solspace\Freeform\Library\Exceptions\Notifications\NotificationException;
use Solspace\Freeform\Records\NotificationTemplateRecord;
use Solspace\Freeform\Services\BaseService;

class NotificationFilesService extends BaseService implements NotificationsServiceInterface
{
    public function getAll(bool $indexById = true): array
    {
        $notifications = [];

        $settings = Freeform::getInstance()->settings->getSettingsModel();
        foreach ($settings->listTemplatesInEmailTemplateDirectory() as $filePath => $name) {
            try {
                $model = NotificationTemplateRecord::createFromTemplate($filePath, false);

                $notifications[$model->filepath] = $model;
            } catch (EmailTemplateException $exception) {
                \Craft::$app->session->setError(
                    Freeform::t(
                        '{template}: {message}',
                        [
                            'template' => $name,
                            'message' => $exception->getMessage(),
                        ]
                    )
                );
            }
        }

        if (!$indexById) {
            return array_values($notifications);
        }

        return $notifications;
    }

    public function getById(mixed $id, bool $failOnError = true): ?NotificationTemplateRecord
    {
        $settings = Freeform::getInstance()->settings->getSettingsModel();
        foreach ($settings->listTemplatesInEmailTemplateDirectory() as $filePath => $name) {
            if ($id === $name) {
                try {
                    return NotificationTemplateRecord::createFromTemplate($filePath, $failOnError);
                } catch (EmailTemplateException $exception) {
                    \Craft::$app->session->setError(
                        Freeform::t(
                            '{template}: {message}',
                            [
                                'template' => $name,
                                'message' => $exception->getMessage(),
                            ]
                        )
                    );
                }
            }
        }

        return null;
    }

    public function create(string $name): NotificationTemplateRecord
    {
        $settings = $this->getSettingsService()->getSettingsModel();

        $templateName = StringHelper::toSnakeCase($name);
        $extension = '.twig';

        $templateDirectory = $settings->getAbsoluteEmailTemplateDirectory();
        if (null === $templateDirectory) {
            throw new NotificationException(
                Freeform::t('Email Template directory not set'),
                NotificationException::NO_EMAIL_DIR,
            );
        }

        $templatePath = $templateDirectory.'/'.$templateName.$extension;
        if (file_exists($templatePath)) {
            throw new NotificationException(
                Freeform::t("Template '{name}' already exists", ['name' => $templateName.$extension]),
                NotificationException::EXISTS,
            );
        }

        try {
            FileHelper::writeToFile($templatePath, $settings->getEmailTemplateContent());
        } catch (\Exception) {
            throw new NotificationException(
                'Could not get email template content. Please contact Solspace.',
                NotificationException::NO_CONTENT,
            );
        }

        return $this->getById($templateName.$extension);
    }

    public function save(NotificationTemplateRecord $record): bool
    {
        $emailDirectory = Freeform::getInstance()->settings->getSettingsModel()->getAbsoluteEmailTemplateDirectory();

        $filepath = $emailDirectory.'/'.$record->filepath;
        if (!file_exists($filepath)) {
            $record->addError('handle', 'File does not exist');

            return false;
        }

        $includeAttachments = $record->isIncludeAttachmentsEnabled() ? 'true' : 'false';
        $presetAssets = $record->getPresetAssets();
        if ($presetAssets) {
            // Note, we dont do anything if its using a Twig tag
            if (\is_array($presetAssets)) {
                $presetAssets = implode(',', $presetAssets);
            }
        } else {
            $presetAssets = '';
        }

        $pdfTemplateIds = $record->getPdfTemplateIds();
        if (\is_array($pdfTemplateIds)) {
            $pdfTemplateIds = implode(',', $pdfTemplateIds);
        } else {
            $pdfTemplateIds = '';
        }

        $output = "{# subject: {$record->getSubject()} #}".\PHP_EOL;
        $output .= "{# fromEmail: {$record->getFromEmail()} #}".\PHP_EOL;
        $output .= "{# fromName: {$record->getFromName()} #}".\PHP_EOL;
        $output .= "{# replyToName: {$record->getReplyToName()} #}".\PHP_EOL;
        $output .= "{# replyToEmail: {$record->getReplyToEmail()} #}".\PHP_EOL;
        $output .= "{# cc: {$record->getCc()} #}".\PHP_EOL;
        $output .= "{# bcc: {$record->getBcc()} #}".\PHP_EOL;
        $output .= "{# includeAttachments: {$includeAttachments} #}".\PHP_EOL;
        $output .= "{# presetAssets: {$presetAssets} #}".\PHP_EOL;
        $output .= "{# pdfTemplates: {$pdfTemplateIds} #}".\PHP_EOL;
        $output .= "{# templateName: {$record->name} #}".\PHP_EOL;
        $output .= "{# description: {$record->description} #}".\PHP_EOL;
        $output .= \PHP_EOL;

        $output .= $record->getBodyHtml();
        $output .= \PHP_EOL;

        if (!$record->isAutoText()) {
            $output .= '{# text #}'.\PHP_EOL;
            $output .= $record->getBodyText();
            $output .= \PHP_EOL.'{# /text #}';
            $output .= \PHP_EOL;
        }

        $newName = $record->handle.'.twig';
        if ($newName !== $record->filepath) {
            unlink($filepath);
            $filepath = $emailDirectory.'/'.$newName;
        }

        $resource = fopen($filepath, 'w');
        fwrite($resource, $output);
        fclose($resource);

        $record->id = $newName;

        return true;
    }

    public function delete(mixed $id): bool
    {
        $record = $this->getById($id);
        if (!$record) {
            return false;
        }

        $emailDirectory = $this->getSettingsService()->getSettingsModel()->getAbsoluteEmailTemplateDirectory();
        unlink($emailDirectory.'/'.$record->filepath);

        return true;
    }
}
