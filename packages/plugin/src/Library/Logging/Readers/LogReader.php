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

namespace Solspace\Freeform\Library\Logging\Readers;

use Solspace\Freeform\Library\Logging\Parsers\LogParserInterface;
use Solspace\Freeform\Library\Logging\Parsers\MonologLineParser;

abstract class LogReader implements LogReaderInterface
{
    public function __construct(private ?LogParserInterface $parser = null)
    {
        if (null === $this->parser) {
            $this->parser = new MonologLineParser();
        }
    }

    public function getParser(): LogParserInterface
    {
        return $this->parser;
    }
}
