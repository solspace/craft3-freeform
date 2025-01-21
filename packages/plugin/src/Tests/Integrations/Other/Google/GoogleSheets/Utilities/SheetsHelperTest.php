<?php

namespace Solspace\Freeform\Tests\Integrations\Other\Google\GoogleSheets\Utilities;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Integrations\Other\Google\GoogleSheets\Utilities\SheetsHelper;

#[CoversClass(SheetsHelper::class)]
class SheetsHelperTest extends TestCase
{
    public function testGetColumnLetter()
    {
        $this->assertSame('A', SheetsHelper::getColumnLetter(0));
        $this->assertSame('Z', SheetsHelper::getColumnLetter(25));
        $this->assertSame('AA', SheetsHelper::getColumnLetter(26));
        $this->assertSame('AB', SheetsHelper::getColumnLetter(27));
        $this->assertSame('AZ', SheetsHelper::getColumnLetter(51));
        $this->assertSame('BA', SheetsHelper::getColumnLetter(52));
        $this->assertSame('ZZ', SheetsHelper::getColumnLetter(701));
        $this->assertSame('AAA', SheetsHelper::getColumnLetter(702));
    }
}
