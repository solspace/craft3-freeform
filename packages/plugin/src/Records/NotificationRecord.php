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
use Solspace\Freeform\Library\DataObjects\EmailTemplate;
use Solspace\Freeform\Library\Helpers\TwigHelper;
use Solspace\Freeform\Library\Mailing\NotificationInterface;

/**
 * Class Freeform_NotificationRecord.
 *
 * @property int    $id
 * @property string $filepath
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
class NotificationRecord extends ActiveRecord implements NotificationInterface, \JsonSerializable
{
    public const TABLE = '{{%freeform_notifications}}';

    /** @var string */
    public $filepath;

    public static function tableName(): string
    {
        return self::TABLE;
    }

    public static function create(): self
    {
        $record = new self();
        $record->fromEmail = \Craft::$app->projectConfig->get('email.fromEmail');
        $record->fromName = \Craft::$app->projectConfig->get('email.fromName');
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

    /**
     * @param string $filePath
     */
    public static function createFromTemplate($filePath): self
    {
        $template = new EmailTemplate($filePath);

        if (\is_string($template->isIncludeAttachments())) {
            $includeAttachments = 'true' === strtolower($template->isIncludeAttachments()) || '1' === $template->isIncludeAttachments();
        } else {
            $includeAttachments = $template->isIncludeAttachments();
        }

        $model = new self();
        $model->filepath = pathinfo($filePath, \PATHINFO_BASENAME);
        $model->name = $template->getName();
        $model->handle = $template->getHandle();
        $model->description = $template->getDescription();
        $model->fromEmail = $template->getFromEmail();
        $model->fromName = $template->getFromName();
        $model->cc = $template->getCc();
        $model->bcc = $template->getBcc();
        $model->subject = $template->getSubject();
        $model->replyToName = $template->getReplyToName();
        $model->replyToEmail = $template->getReplyToEmail();
        $model->bodyHtml = $template->getBody();
        $model->bodyText = $template->getTextBody();
        $model->includeAttachments = $includeAttachments;
        $model->presetAssets = $template->getPresetAssets();
        $model->autoText = $template->isAutoText();

        return $model;
    }

    public function rules(): array
    {
        return [
            [['name', 'handle', 'subject', 'fromName', 'fromEmail'], 'required'],
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

    public function getPresetAssets(): array|string|null
    {
        if ($this->presetAssets) {
            if (TwigHelper::isTwigValue($this->presetAssets)) {
                return $this->presetAssets;
            }

            return json_decode($this->presetAssets, true);
        }

        return null;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => is_numeric($this->id) ? (int) $this->id : $this->id,
            'name' => $this->name,
            'handle' => $this->filepath,
            'description' => $this->description,
        ];
    }
}
