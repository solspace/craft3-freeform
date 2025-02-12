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

use Solspace\Freeform\Library\Logging\Parsers\LogLine;
use Solspace\Freeform\Library\Logging\Parsers\LogParserInterface;

class FileLogReader extends LogReader
{
    public const DEFAULT_NUMBER_OF_LINES = 15;

    protected int $lineCount;
    protected ?\SplFileObject $file;

    public function __construct(string $filePath, ?LogParserInterface $parser = null)
    {
        parent::__construct($parser);

        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('File does not exist: '.$filePath);
        }

        $file = new \SplFileObject($filePath, 'r');
        $file->setFlags(\SplFileObject::SKIP_EMPTY);

        $i = 0;
        while ($file->valid()) {
            $file->next();
            if (!empty($file->current())) {
                ++$i;
            }
        }

        $file->rewind();

        $this->lineCount = $i;
        $this->file = $file;
    }

    public function getLines(int $count = self::DEFAULT_NUMBER_OF_LINES, int $offset = 0, ?bool $readFromEnd = true): array
    {
        if ($readFromEnd) {
            return $this->readFromEnd($count, $offset);
        }

        return $this->readFromStart($count, $offset);
    }

    public function rewind(): void
    {
        $this->file->rewind();
    }

    public function next(): void
    {
        $this->file->next();
    }

    public function current(): ?LogLine
    {
        return $this->getParser()->parse($this->file->current());
    }

    public function key()
    {
        return $this->file->key();
    }

    public function valid(): bool
    {
        return $this->file->valid();
    }

    public function count(): int
    {
        return $this->lineCount;
    }

    private function readFromEnd(int $count, int $offset): array
    {
        $lines = [];

        $max = max(0, $this->lineCount - $count - $offset);

        $file = $this->file;
        $next = $this->lineCount - 1 - $offset;
        while ($next >= $max && $file->valid()) {
            $file->seek($next--);

            $parsed = $this->getParser()->parse($file->current());
            if ($parsed) {
                $lines[] = $parsed;
            }
        }

        $file->rewind();

        return $lines;
    }

    private function readFromStart(int $count, int $offset): array
    {
        $lines = [];

        $max = min($this->lineCount, $count + $offset);

        $file = $this->file;
        $next = $offset;
        while ($next < $max && $file->valid()) {
            $file->seek($next++);

            $parsed = $this->getParser()->parse($file->current());
            if ($parsed) {
                $lines[] = $parsed;
            }
        }

        $file->rewind();

        return $lines;
    }
}
