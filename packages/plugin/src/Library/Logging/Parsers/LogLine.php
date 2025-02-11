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
    private string $channel;
    private string $level;
    private string $message;
    private ?string $context = null;
    private ?\stdClass $extra = null;

    public function __construct(array $data)
    {
        $this->date = new \DateTime($data['date']);
        $this->channel = $data['channel'];
        $this->level = $data['level'];
        $this->message = $data['message'];

        $context = $data['context'] ? trim($data['context']) : null;
        if (!empty($context) && '[]' !== $context) {
            $context = json_decode($context, true);
            $this->context = json_encode($context, \count($context) > 1 ? \JSON_PRETTY_PRINT : 0);
        }

        $extra = $data['extra'] ? trim($data['extra']) : null;
        if (!empty($extra) && '[]' !== $extra) {
            $this->extra = json_decode($extra);
        }
    }

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

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function getExtra(): ?\stdClass
    {
        return $this->extra;
    }
}
