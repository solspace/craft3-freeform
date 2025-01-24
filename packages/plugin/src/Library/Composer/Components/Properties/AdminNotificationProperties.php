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

namespace Solspace\Freeform\Library\Composer\Components\Properties;

use Solspace\Freeform\Elements\Submission;

class AdminNotificationProperties extends AbstractProperties
{
    /** @var int */
    protected $notificationId;

    /** @var string */
    protected $recipients;

    /**
     * @return int|string
     */
    public function getNotificationId()
    {
        return $this->notificationId;
    }

    /**
     * @return string
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * Gets all recipients as an array.
     */
    public function getRecipientArray(Submission $submission = null): array
    {
        $recipients = $this->getRecipients();

        // Handle any Twig used in the recipients, and swallow any errors
        if ($submission) {
            try {
                $recipients = \Craft::$app->getView()->renderString($recipients, $submission->toArray());
            } catch (\Throwable $e) {
            }
        }

        if (empty($recipients)) {
            return [];
        }

        $list = preg_split("/\r\n|\n|\r/", $recipients);
        $list = array_map('trim', $list);
        $list = array_unique($list);

        return array_filter($list);
    }

    /**
     * Return a list of all property fields and their type.
     *
     * [propertyKey => propertyType, ..]
     * E.g. ["name" => "string", ..]
     */
    protected function getPropertyManifest(): array
    {
        return [
            'notificationId' => self::TYPE_STRING,
            'recipients' => self::TYPE_STRING,
        ];
    }
}
