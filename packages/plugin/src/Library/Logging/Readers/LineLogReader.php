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

class LineLogReader extends AbstractLogReader implements \Iterator, \Countable
{
    public const DEFAULT_NUMBER_OF_LINES = 15;

    protected int $lineCount = 0;

    protected ?\SplFileObject $file;

    protected LogParserInterface $parser;

    public function __construct(string $filePath, ?string $defaultPatternPattern = null)
    {
        parent::__construct($defaultPatternPattern);

        if (!file_exists($filePath)) {
            $this->file = null;
            $this->lineCount = 0;

            return;
        }

        $this->file = new \SplFileObject($filePath, 'r');
        $this->file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);

        $i = 0;

        while (!$this->file->eof()) {
            $line = $this->file->current();

            if (!empty($line)) {
                ++$i;
            }

            $this->file->next();
        }

        $this->lineCount = $i;

        $this->setParser($this->getDefaultParser());
    }

    public function setParser(LogParserInterface $parser): self
    {
        $this->parser = $parser;

        return $this;
    }

    public function getParser(): LogParserInterface
    {
        return $this->parser;
    }

    public function getLastLines(int $numberOfLines = self::DEFAULT_NUMBER_OF_LINES): array
    {
        $lines = [];

        if (null === $this->file) {
            return $lines;
        }

        $this->file->seek($this->lineCount);

        $currentKey = $this->file->key();

        while ($currentKey >= 0) {
            $line = $this->getDefaultParser()->parse($this->file->current());

            if ($line) {
                $lines[] = $line;
            }

            if (--$currentKey >= 0) {
                $this->file->seek($currentKey);
            }

            if (\count($lines) >= $numberOfLines) {
                break;
            }
        }

        return $lines;
    }

    public function rewind()
    {
        if (null !== $this->file) {
            $this->file->rewind();
        }
    }

    public function next()
    {
        if (null !== $this->file) {
            $this->file->next();
        }
    }

    public function current()
    {
        if (null !== $this->file) {
            return $this->parser->parse($this->file->current());
        }

        return null;
    }

    public function key()
    {
        if (null !== $this->file) {
            return $this->file->key();
        }

        return null;
    }

    public function valid()
    {
        if (null !== $this->file) {
            return $this->file->valid();
        }

        return false;
    }

    public function count()
    {
        return $this->lineCount;
    }
}
