<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\FileHelper;

#[CoversClass(FileHelper::class)]
class FileHelperTest extends TestCase
{
    #[TestWith(['/path/to/file', true])]
    #[TestWith(['path/to/file', false])]
    #[TestWith(['C:\path\to\file', true])]
    #[TestWith(['D:/path/to/file', true])]
    #[TestWith(['ZD:\path\to\file', true])]
    #[TestWith(['//path/to/file', true])]
    public function testIsAbsolute(string $path, bool $expected)
    {
        $this->assertSame($expected, FileHelper::isAbsolute($path), $path);
    }
}
