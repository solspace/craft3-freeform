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

namespace Solspace\Freeform\Library\DataObjects;

use Solspace\Commons\Helpers\StringHelper;
use Solspace\Freeform\Library\Exceptions\DataObjects\EmailTemplateException;
use Solspace\Freeform\Library\Helpers\TwigHelper;

class EmailTemplate
{
    public const METADATA_PATTERN = '/{#\\s*__KEY__:\\s*(.*)#}/';

    /** @var string */
    private $name;

    /** @var string */
    private $handle;

    /** @var string */
    private $description;

    /** @var string */
    private $templateData;

    /** @var string */
    private $fromEmail;

    /** @var string */
    private $fromName;

    /** @var string */
    private $cc;

    /** @var string */
    private $bcc;

    /** @var string */
    private $replyToName;

    /** @var string */
    private $replyToEmail;

    /** @var bool */
    private $includeAttachments;

    /** @var array */
    private $presetAssets = [];

    /** @var string */
    private $subject;

    /** @var string */
    private $body;

    /** @var string */
    private $textBody;

    /** @var bool */
    private $autoText;

    /**
     * EmailTemplate constructor.
     *
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->templateData = file_get_contents($filePath);

        $this->handle = pathinfo($filePath, \PATHINFO_FILENAME);

        $name = $this->getMetadata('templateName');
        if (!$name) {
            $name = StringHelper::camelize(StringHelper::humanize(pathinfo($filePath, \PATHINFO_FILENAME)));
        }

        $this->name = $name;

        $this->description = $this->getMetadata('description');
        $this->fromEmail = $this->getMetadata('fromEmail', true);
        $this->fromName = $this->getMetadata('fromName', true);
        $this->cc = $this->getMetadata('cc');
        $this->bcc = $this->getMetadata('bcc');
        $this->replyToName = $this->getMetadata('replyToName');
        $this->replyToEmail = $this->getMetadata('replyToEmail');
        $this->subject = $this->getMetadata('subject', true);

        $body = $text = $this->templateData;

        $textPattern = "/({#\\s*text\\s*#}\n?)([\\s\\S]*)({#\\s*\\/text\\s*#}\n?)/";
        if (preg_match($textPattern, $body, $textMatches)) {
            $text = $textMatches[2];
            $body = preg_replace($textPattern, '', $this->templateData);
            $this->autoText = false;
        } else {
            $this->autoText = true;
        }

        $body = preg_replace("/{#.*#}\n?/", '', $body);
        $text = preg_replace("/{#.*#}\n?/", '', $text);

        $this->body = $body;
        $this->textBody = $text;

        $includeAttachments = $this->getMetadata('includeAttachments');
        $this->includeAttachments = $includeAttachments && 'true' === strtolower($includeAttachments);

        $presetAssets = $this->getMetadata('presetAssets');
        if ($presetAssets) {
            $isTwigValue = TwigHelper::isTwigValue($presetAssets);
            if ($isTwigValue) {
                $this->presetAssets = $presetAssets;
            } else {
                $this->presetAssets = StringHelper::extractSeparatedValues($presetAssets);
            }
        }

        $this->includeAttachments = $includeAttachments;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * @return string
     */
    public function getFromName()
    {
        return $this->fromName;
    }

    /**
     * @return string
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * @return string
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * @return string
     */
    public function getReplyToName()
    {
        return $this->replyToName;
    }

    /**
     * @return string
     */
    public function getReplyToEmail()
    {
        return $this->replyToEmail;
    }

    /**
     * @return bool
     */
    public function isIncludeAttachments()
    {
        return $this->includeAttachments;
    }

    /**
     * @return array
     */
    public function getPresetAssets()
    {
        return $this->presetAssets;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getTextBody()
    {
        return $this->textBody;
    }

    public function isAutoText(): bool
    {
        return (bool) $this->autoText;
    }

    /**
     * @param string $key
     * @param bool   $required
     *
     * @return null|string
     *
     * @throws EmailTemplateException
     */
    private function getMetadata($key, $required = false)
    {
        $value = null;
        $pattern = str_replace('__KEY__', $key, self::METADATA_PATTERN);

        if (preg_match($pattern, $this->templateData, $matches)) {
            [$_, $value] = $matches;
            $value = trim($value);
        } elseif ($required) {
            throw new EmailTemplateException(sprintf("Email template does not contain '%s'", $key));
        }

        return $value;
    }
}
