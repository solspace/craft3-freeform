<?php
/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2025, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Services;

use craft\mail\Message;
use craft\web\View;
use Solspace\Commons\Helpers\StringHelper;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Events\Mailer\RenderEmailEvent;
use Solspace\Freeform\Events\Mailer\SendEmailEvent;
use Solspace\Freeform\Fields\HtmlField;
use Solspace\Freeform\Fields\Pro\RichTextField;
use Solspace\Freeform\Fields\Pro\SignatureField;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\Composer\Components\FieldInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\FileUploadInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\NoStorageInterface;
use Solspace\Freeform\Library\Composer\Components\Fields\Interfaces\PaymentInterface;
use Solspace\Freeform\Library\Composer\Components\Form;
use Solspace\Freeform\Library\Helpers\TwigHelper;
use Solspace\Freeform\Library\Logging\FreeformLogger;
use Solspace\Freeform\Library\Mailing\MailHandlerInterface;
use Solspace\Freeform\Library\Mailing\NotificationInterface;
use Solspace\Freeform\Records\NotificationRecord;
use Twig\Error\LoaderError as TwigLoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError as TwigSyntaxError;

class MailerService extends BaseService implements MailHandlerInterface
{
    public const EVENT_BEFORE_SEND = 'beforeSend';
    public const EVENT_AFTER_SEND = 'afterSend';
    public const EVENT_BEFORE_RENDER = 'beforeRender';

    public const ERROR_CODE_LINES_PROXIMITY = 5;

    public const LOG_CATEGORY = 'freeform_notifications';

    /**
     * Send out an email to recipients using the given mail template.
     */
    public function sendEmail(
        Form $form,
        array|string $recipients,
        NotificationRecord $notification = null,
        array $fields = [],
        ?Submission $submission = null
    ): int {
        $logger = FreeformLogger::getInstance(FreeformLogger::MAILER);
        $sentMailCount = 0;

        if (null === $notification) {
            return $sentMailCount;
        }

        $recipients = $this->processRecipients($recipients);

        $fieldValues = $this->getFieldValues($fields, $form, $submission);
        $renderEvent = new RenderEmailEvent($form, $notification, $fieldValues, $submission);

        $this->trigger(self::EVENT_BEFORE_RENDER, $renderEvent);
        $fieldValues = $renderEvent->getFieldValues();

        $templateMode = \Craft::$app->view->getTemplateMode();
        \Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_SITE);

        foreach ($recipients as $recipientName => $emailAddress) {
            if (filter_var($recipientName, \FILTER_VALIDATE_EMAIL)) {
                $emailAddress = $recipientName;
            }

            try {
                $email = $this->compileMessage($notification, $fieldValues);
                $email->setTo([$emailAddress]);

                if ($submission && $notification->isIncludeAttachmentsEnabled()) {
                    foreach ($fields as $field) {
                        if ($field instanceof SignatureField && $field->getValueAsString()) {
                            $email->attach($field->getValueAsString(), [
                                'fileName' => 'signature.png',
                                'contentType' => 'image/png',
                            ]);

                            continue;
                        }

                        if (!$field instanceof FileUploadInterface || !$field->getHandle()) {
                            continue;
                        }

                        $fieldValue = $submission->{$field->getHandle()}->getValue();
                        $assetIds = $fieldValue;
                        foreach ($assetIds as $assetId) {
                            $asset = \Craft::$app->assets->getAssetById((int) $assetId);
                            if ($asset) {
                                $email->attach(
                                    $asset->getCopyOfFile(),
                                    ['fileName' => $asset->filename]
                                );
                            }
                        }
                    }
                }

                $sendEmailEvent = new SendEmailEvent($email, $form, $notification, $fieldValues, $submission);
                $this->trigger(self::EVENT_BEFORE_SEND, $sendEmailEvent);

                if (!$sendEmailEvent->isValid) {
                    continue;
                }

                $emailSent = \Craft::$app->mailer->send($email);

                $this->trigger(self::EVENT_AFTER_SEND, $sendEmailEvent);

                if ($emailSent) {
                    ++$sentMailCount;
                }
            } catch (\Exception $exception) {
                $message = $exception->getMessage();
                $context = [
                    'template' => $notification->getHandle(),
                    'file' => $exception->getFile(),
                ];

                $logger->error($message, $context);

                $this->notifyAboutEmailSendingError($emailAddress, $notification, $exception, $form);
            } finally {
                \Craft::$app->view->setTemplateMode($templateMode);
            }
        }

        return $sentMailCount;
    }

    /**
     * @param int $id
     *
     * @return null|NotificationInterface
     */
    public function getNotificationById($id)
    {
        return Freeform::getInstance()->notifications->getNotificationById($id);
    }

    /**
     * Renders a template defined in a string.
     *
     * @param string $template  the source template string
     * @param array  $variables any variables that should be available to the template
     *
     * @return string the rendered template
     *
     * @throws TwigLoaderError
     * @throws TwigSyntaxError
     */
    public function renderString(string $template, array $variables = []): string
    {
        if (preg_match('/^\$(\w+)$/', $template, $matches)) {
            return \Craft::parseEnv($template);
        }

        return \Craft::$app->view->getTwig()
            ->createTemplate($template)
            ->render($variables);
    }

    public function compileMessage(NotificationInterface $notification, array $values): Message
    {
        $fromName = trim(\Craft::parseEnv($this->renderString($notification->getFromName(), $values)));
        $fromEmail = trim(\Craft::parseEnv($this->renderString($notification->getFromEmail(), $values)));
        $text = $this->renderString($notification->getBodyText(), $values);
        $html = $this->renderString($notification->getBodyHtml(), $values);
        $subject = $this->renderString($notification->getSubject(), $values);
        $subject = htmlspecialchars_decode($subject, \ENT_QUOTES);

        $message = new Message();
        $message->variables = $values;
        $message
            ->setFrom([$fromEmail => $fromName])
            ->setSubject($subject)
        ;

        if (empty($text)) {
            $message
                ->setHtmlBody($html)
                ->setTextBody($html)
            ;
        }

        if (empty($html)) {
            $message->setTextBody($text);
        } else {
            $message
                ->setHtmlBody($html)
                ->setTextBody($text)
            ;
        }

        if ($notification->getCc()) {
            $cc = $this->renderString($notification->getCc(), $values);
            $cc = StringHelper::extractSeparatedValues($cc);
            if (!empty($cc)) {
                $message->setCc($this->parseEnvInArray($cc));
            }
        }

        if ($notification->getBcc()) {
            $bcc = $this->renderString($notification->getBcc(), $values);
            $bcc = StringHelper::extractSeparatedValues($bcc);
            if (!empty($bcc)) {
                $message->setBcc($this->parseEnvInArray($bcc));
            }
        }

        if ($notification->getReplyToEmail()) {
            $replyToName = trim(\Craft::parseEnv($this->renderString($notification->getReplyToName() ?? '', $values)));
            $replyTo = trim(\Craft::parseEnv($this->renderString($notification->getReplyToEmail(), $values)));
            if (!empty($replyTo)) {
                if ($replyToName) {
                    $replyTo = [$replyTo => $replyToName];
                }

                $message->setReplyTo($replyTo);
            }
        }

        $presetAssets = $notification->getPresetAssets();

        if ($presetAssets && Freeform::getInstance()->isPro()) {
            if (!\is_array($presetAssets) && TwigHelper::isTwigValue($presetAssets)) {
                $presetAssets = trim(\Craft::parseEnv($this->renderString($presetAssets, $values)));

                $delimiters = [',', '.', '|', '!', '?'];

                // Changes '1! 2. 3, 4| 5? 6' --> '1,2,3,4,5,6'
                $presetAssets = str_replace($delimiters, $delimiters[0], $presetAssets);
                $presetAssets = explode($delimiters[0], $presetAssets);
                $presetAssets = array_filter($presetAssets);
            }

            foreach ($presetAssets as $assetId) {
                $asset = \Craft::$app->assets->getAssetById((int) $assetId);
                if ($asset) {
                    $message->attach(
                        $asset->getCopyOfFile(),
                        ['fileName' => $asset->filename]
                    );
                }
            }
        }

        return $message;
    }

    public function processRecipients($recipients): array
    {
        if (version_compare(\Craft::$app->getVersion(), '3.5', '>=')) {
            $testToEmailAddress = \Craft::$app->getConfig()->getGeneral()->getTestToEmailAddress();
            if (!empty($testToEmailAddress)) {
                return $testToEmailAddress;
            }
        }

        if (!\is_array($recipients)) {
            $recipients = $recipients ? [$recipients] : [];
        }

        $processedRecipients = [];
        foreach ($recipients as $index => $value) {
            $exploded = explode(',', $value);
            foreach ($exploded as $emailString) {
                $processedRecipients[] = trim($emailString);
            }
        }

        return array_filter($processedRecipients);
    }

    /**
     * @return array
     */
    private function parseEnvInArray(array $array)
    {
        $parsed = [];
        foreach ($array as $key => $item) {
            $parsed[$key] = trim(\Craft::parseEnv($item));
        }

        return $parsed;
    }

    /**
     * @param FieldInterface[] $fields
     * @param Submission       $submission
     */
    private function getFieldValues(array $fields, Form $form, Submission $submission = null): array
    {
        $postedValues = [];
        $usableFields = [];
        $fieldsAndBlocks = [];
        $rules = $form->getRuleProperties();

        foreach ($fields as $field) {
            if ($field instanceof HtmlField || $field instanceof RichTextField) {
                $fieldsAndBlocks[] = $field;
            }

            if ($field instanceof NoStorageInterface
                || $field instanceof FileUploadInterface
                || $field instanceof PaymentInterface
                || $field instanceof SignatureField
            ) {
                continue;
            }

            if ($rules && $rules->isHidden($field, $form)) {
                continue;
            }

            $fieldsAndBlocks[] = $field;
            $usableFields[] = $field;
            $postedValues[$field->getHandle()] = $field;
        }

        // TODO: offload this call to payments plugin with an event
        if ($submission && $form->getLayout()->hasFields(PaymentInterface::class)) {
            $payments = Freeform::getInstance()->payments->getPaymentDetails(
                $submission->getId(),
                $submission->getForm()
            );
            $postedValues['payments'] = $payments;
        }

        $postedValues['allFields'] = $usableFields;
        $postedValues['allFieldsAndBlocks'] = $fieldsAndBlocks;
        $postedValues['form'] = $form;
        $postedValues['submission'] = $submission;
        $postedValues['dateCreated'] = new \DateTime();
        $postedValues['token'] = $submission ? $submission->token : null;

        return $postedValues;
    }

    private function notifyAboutEmailSendingError(
        string $failedRecipient,
        NotificationInterface $failedNotification,
        \Exception $exception,
        Form $form
    ) {
        $recipients = $this->getSettingsService()->getFailedNotificationRecipients();
        if (!\count($recipients)) {
            return;
        }

        $recipients = $this->processRecipients($recipients);

        $templateMode = \Craft::$app->view->getTemplateMode();
        \Craft::$app->view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $notificationPath = __DIR__.'/../templates/_emailTemplates/error-notify.twig';
        $notification = NotificationRecord::createFromTemplate($notificationPath);

        $code = null;
        if ($exception instanceof RuntimeError) {
            $line = $exception->getTemplateLine();
            $code = $exception->getSourceContext()->getCode();
            $source = explode(\PHP_EOL, $code);
            $proximity = self::ERROR_CODE_LINES_PROXIMITY;

            $code = [
                'lines' => [
                    'first' => max(1, $line - $proximity),
                    'last' => min(\count($source), $line + $proximity),
                    'highlight' => $line,
                ],
                'source' => $source,
            ];
        }

        $message = $this->compileMessage(
            $notification,
            [
                'form' => $form,
                'recipient' => $failedRecipient,
                'exception' => $exception,
                'notification' => $failedNotification,
                'code' => $code,
            ]
        );

        $message->setTo($recipients);

        \Craft::$app->mailer->send($message);
        \Craft::$app->view->setTemplateMode($templateMode);
    }
}
