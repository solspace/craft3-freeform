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
    public function __construct(
        private \DateTime $date,
        private string $channel,
        private string $level,
        private string $message,
        private ?array $context = null,
        private ?array $extra = null,
    ) {}

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): ?array
    {
        return $this->context;
    }

    public function getExtra(): ?array
    {
        return $this->extra;
    }
}
