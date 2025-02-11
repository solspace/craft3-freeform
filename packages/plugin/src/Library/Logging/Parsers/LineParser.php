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

class LineParser implements LogParserInterface
{
    private string $pattern = '/^'.
        '\[(?<date>.*)] '.
        '(?<channel>[\w-]+).(?<level>\w+): '.
        '(?<message>.*)'.
        '(?<context> (\[.*?]|\{.*?}))'.
        '(?<extra> (\{.*}))'.
        '\s{0,2}$/';

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

        return new LogLine($data);
    }
}
