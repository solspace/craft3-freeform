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

namespace Solspace\Freeform\Records;

use craft\db\ActiveRecord;
use Solspace\Freeform\Freeform;
use Solspace\Freeform\Library\DataObjects\NotificationTemplate;
use Solspace\Freeform\Library\Helpers\TwigHelper;

/**
 * @property int    $id
 * @property string $pdfTemplateIds
 * @property string $name
 * @property string $handle
 * @property string $description
 * @property string $fromName
 * @property string $fromEmail
 * @property string $replyToName
 * @property string $replyToEmail
 * @property bool   $includeAttachments
 * @property string $subject
 * @property string $bodyHtml
 * @property string $bodyText
 * @property int    $sortOrder
 * @property string $cc
 * @property string $bcc
 * @property string $presetAssets
 * @property bool   $autoText
 */
class NotificationTemplateRecord extends ActiveRecord
{
    public const TABLE = '{{%freeform_notification_templates}}';

    public ?string $filepath = null;

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public static function create(): self
    {
        $settingsModel = Freeform::getInstance()->settings->getSettingsModel();

        $record = new self();
        $record->fromEmail = $settingsModel->defaultFromEmail ?: "{{ craft.app.projectConfig.get('email.fromEmail') }}";
        $record->fromName = $settingsModel->defaultFromName ?: "{{ craft.app.projectConfig.get('email.fromName') }}";
        $record->subject = 'New submission on your {{ form.name }} form';
        $record->autoText = true;
        $record->bodyHtml = <<<'EOT'
            <p>Submitted on: {{ dateCreated|date('l, F j, Y \\a\\t g:ia') }}</p>
            <ul>
            {% for field in allFields %}
                <li>{{ field.label }}: {{ field.valueAsString }}</li>
            {% endfor %}
            </ul>
            EOT;
        $record->bodyText = <<<'EOT'
            Submitted on: {{ dateCreated|date('l, F j, Y \\a\\t g:ia') }}

            {% for field in allFields %}
             - {{ field.label }}: {{ field.valueAsString }}
            {% endfor %}
            EOT;

        return $record;
    }

    public static function createFromTemplate(string $filePath, bool $failOnError = true): self
    {
        $template = NotificationTemplate::fromFile($filePath, $failOnError);

        if (\is_string($template->isIncludeAttachments())) {
            $includeAttachments = 'true' === strtolower($template->isIncludeAttachments()) || '1' === $template->isIncludeAttachments();
        } else {
            $includeAttachments = $template->isIncludeAttachments();
        }

        $record = new self();
        $record->filepath = pathinfo($filePath, \PATHINFO_BASENAME);
        $record->pdfTemplateIds = $template->getPdfTemplateIds();
        $record->name = $template->getName();
        $record->handle = $template->getHandle();
        $record->description = $template->getDescription();
        $record->fromEmail = $template->getFromEmail();
        $record->fromName = $template->getFromName();
        $record->cc = $template->getCc();
        $record->bcc = $template->getBcc();
        $record->subject = $template->getSubject();
        $record->replyToName = $template->getReplyToName();
        $record->replyToEmail = $template->getReplyToEmail();
        $record->bodyHtml = $template->getBody();
        $record->bodyText = $template->getTextBody();
        $record->includeAttachments = (bool) $includeAttachments;
        $record->presetAssets = $template->getPresetAssets();
        $record->autoText = $template->isAutoText();

        return $record;
    }

    public function rules(): array
    {
        return [
            [['name', 'handle', 'subject', 'fromName', 'fromEmail'], 'required'],
            [['handle'], 'unique'],
            [
                'bodyHtml',
                'required',
                'when' => function ($model) {
                    return empty($model->bodyText);
                },
                'message' => 'Either HTML or Text body must be present',
            ],
            [
                'bodyText',
                'required',
                'when' => function ($model) {
                    return empty($model->bodyHtml);
                },
                'message' => 'Either HTML or Text body must be present',
            ],
        ];
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function isFileBasedTemplate(): bool
    {
        return (bool) $this->filepath;
    }

    public function getFromName(): string
    {
        return $this->fromName;
    }

    public function getFromEmail(): string
    {
        return $this->fromEmail;
    }

    public function getCc()
    {
        return $this->cc;
    }

    public function getBcc()
    {
        return $this->bcc;
    }

    public function getReplyToName(): ?string
    {
        return $this->replyToName;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function isIncludeAttachmentsEnabled(): bool
    {
        return (bool) $this->includeAttachments;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function getBodyHtml(): string
    {
        return $this->bodyHtml;
    }

    public function getBodyText(): string
    {
        if ($this->isAutoText()) {
            return strip_tags($this->bodyHtml);
        }

        return $this->bodyText;
    }

    public function isAutoText(): bool
    {
        return (bool) $this->autoText;
    }

    public function getPresetAssets(): null|array|string
    {
        if ($this->presetAssets) {
            if (TwigHelper::isTwigValue($this->presetAssets)) {
                return $this->presetAssets;
            }

            return json_decode($this->presetAssets, true);
        }

        return null;
    }

    public function getPdfTemplateIdList(): array
    {
        if (\is_array($this->pdfTemplateIds)) {
            return $this->pdfTemplateIds;
        }

        if (\is_string($this->pdfTemplateIds)) {
            return json_decode($this->pdfTemplateIds, true) ?: [];
        }

        return [];
    }
}
