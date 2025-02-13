<?php

namespace Solspace\Freeform\Tests\Library\Logging\Readers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Logging\Parsers\LogLine;
use Solspace\Freeform\Library\Logging\Readers\FileLogReader;

#[CoversClass(FileLogReader::class)]
class FileLogReaderTest extends TestCase
{
    public function testReadLog()
    {
        $reader = new FileLogReader(__DIR__.'/test.log');
        $this->assertCount(2, $reader);

        $lines = $reader->getLines();

        /** @var LogLine $lastLine */
        $lastLine = $lines[0];
        $this->assertInstanceOf(LogLine::class, $lastLine);

        $this->assertCount(2, $lines);
        $this->assertEquals(new \DateTime('2025-02-11T12:20:24.899599+0000'), $lastLine->getDate());
        $this->assertEquals('test message with context', $lastLine->getMessage());
        $this->assertEquals('test-category', $lastLine->getChannel());
        $this->assertEquals('test category', $lines[1]->getChannel());
        $this->assertEquals('DEBUG', $lastLine->getLevel());
        $this->assertEquals(['password' => 'reda**********', 'username' => 'testuser'], $lastLine->getContext());
    }

    public function testReadFirst5()
    {
        $reader = new FileLogReader(__DIR__.'/test-large.log');
        $lines = $reader->getLines(5, readFromEnd: false);

        $this->assertCount(5, $lines);
        $this->assertSame('test message 0', $lines[0]->getMessage());
        $this->assertSame('test message 4', $lines[4]->getMessage());
    }

    public function testFetchesMaximumAvailable()
    {
        $reader = new FileLogReader(__DIR__.'/test.log');
        $this->assertCount(2, $reader->getLines(100));
        $this->assertCount(2, $reader->getLines(100, readFromEnd: false));
    }

    public function testFetchWithinBounds()
    {
        $reader = new FileLogReader(__DIR__.'/test-large.log');
        $this->assertCount(10, $reader->getLines(10));
        $this->assertCount(10, $reader->getLines(10, readFromEnd: false));
    }

    public function testReadLastOffset()
    {
        $reader = new FileLogReader(__DIR__.'/test-large.log');

        $lines = $reader->getLines(10, 10);
        $this->assertCount(10, $lines);
        $this->assertSame('test message 989', $lines[0]->getMessage());
        $this->assertSame('test message 980', $lines[9]->getMessage());
    }

    public function testReadFirstOffset()
    {
        $reader = new FileLogReader(__DIR__.'/test-large.log');

        $lines = $reader->getLines(10, 10, false);
        $this->assertCount(10, $lines);
        $this->assertSame('test message 10', $lines[0]->getMessage());
        $this->assertSame('test message 19', $lines[9]->getMessage());
    }
}
