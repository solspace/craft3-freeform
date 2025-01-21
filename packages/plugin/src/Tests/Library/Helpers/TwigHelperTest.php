<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\TwigHelper;

#[CoversClass(TwigHelper::class)]
class TwigHelperTest extends TestCase
{
    public function testIsTwigValue()
    {
        $this->assertFalse(TwigHelper::isTwigValue(''));
        $this->assertFalse(TwigHelper::isTwigValue('12345'));
        $this->assertFalse(TwigHelper::isTwigValue('123, 45'));
        $this->assertTrue(TwigHelper::isTwigValue('{{ "12345" }}'));
        $this->assertTrue(TwigHelper::isTwigValue('{{ fieldHandle.value }}'));
        $this->assertTrue(TwigHelper::isTwigValue('{{ submission.fieldhandle.value }}'));
    }
}
