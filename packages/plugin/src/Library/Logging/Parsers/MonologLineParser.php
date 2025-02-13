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

class MonologLineParser implements LogParserInterface
{
    private string $pattern = '/^'.
        '\[(?<date>.*)] '.
        '(?<channel>[\w\-_ ]+).(?<level>\w+): '.
        '(?<message>.*)'.
        '(?<context> (\[.*?]|\{.*?}))'.
        '(?<extra> (\{.*}))'.
        '\s{0,2}/';

    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern ?: $this->pattern;
    }

    public function parse(string $log): ?LogLine
    {
        preg_match($this->pattern, $log, $data);
        if (!isset($data['date'])) {
            return null;
        }

        $date = new \DateTime($data['date']);
        $channel = $data['channel'];
        $level = $data['level'];
        $message = $data['message'];
        $context = isset($data['context']) ? json_decode($data['context'], true) : null;
        $extra = isset($data['extra']) ? json_decode($data['extra'], true) : null;

        return new LogLine(
            $date,
            $channel,
            $level,
            $message,
            $context,
            $extra
        );
    }
}
