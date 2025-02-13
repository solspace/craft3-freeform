<?php

namespace Solspace\Freeform\Library\Logging\Readers;

use Solspace\Freeform\Library\Logging\Parsers\LogParserInterface;

interface LogReaderInterface extends \Iterator, \Countable
{
    public function getParser(): LogParserInterface;
}
