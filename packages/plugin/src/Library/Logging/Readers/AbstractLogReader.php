<?php

/**
 * Freeform for Craft CMS.
 *
 * @author        Solspace, Inc.
 * @copyright     Copyright (c) 2008-2024, Solspace, Inc.
 *
 * @see           https://docs.solspace.com/craft/freeform
 *
 * @license       https://docs.solspace.com/license-agreement
 */

namespace Solspace\Freeform\Library\Logging\Readers;

use Solspace\Freeform\Library\Logging\Parsers\LineParser;
use Solspace\Freeform\Library\Logging\Parsers\LogParserInterface;

class AbstractLogReader
{
    protected ?string $defaultParserPattern;

    public function __construct(?string $defaultParserPattern = null)
    {
        $this->defaultParserPattern = $defaultParserPattern;
    }

    protected function getDefaultParser(): LogParserInterface
    {
        return new LineParser($this->defaultParserPattern);
    }
}
