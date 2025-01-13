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

namespace Solspace\Freeform\Library\Logging\Parsers;

class LogLine
{
    private \DateTime $date;

    private string $logger;

    private string $level;

    private string $message;

    public function __construct(array $data)
    {
        $this->date = new \DateTime($data['date']);
        $this->logger = $data['logger'];
        $this->level = $data['level'];
        $this->message = $data['message'];
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getLogger(): string
    {
        return $this->logger;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}
