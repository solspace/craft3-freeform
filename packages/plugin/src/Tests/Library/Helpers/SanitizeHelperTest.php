<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\SanitizeHelper;

#[CoversClass(SanitizeHelper::class)]
class SanitizeHelperTest extends TestCase
{
    public function testSanitizesString()
    {
        $result = SanitizeHelper::recursiveHtmlSpecialChars('<script />');
        $this->assertSame('&lt;script /&gt;', $result);
    }

    public function testSanitizesSingleDimensionArray()
    {
        $result = SanitizeHelper::recursiveHtmlSpecialChars(['<script />']);

        $this->assertEquals(['&lt;script /&gt;'], $result);
    }

    public function testSanitizesThreeDimensionArray()
    {
        $result = SanitizeHelper::recursiveHtmlSpecialChars(
            [
                '<script />',
                false,
                1,
                ['<script />', ['<script />']],
            ]
        );

        $this->assertEquals(
            [
                '&lt;script /&gt;',
                false,
                1,
                ['&lt;script /&gt;', ['&lt;script /&gt;']],
            ],
            $result
        );
    }

    public function testDoesNotSanitizeIntegers()
    {
        $this->assertSame(1, SanitizeHelper::recursiveHtmlSpecialChars(1));
    }

    public function testDoesNotSanitizeBooleans()
    {
        $this->assertFalse(SanitizeHelper::recursiveHtmlSpecialChars(false));
    }

    public function testSanitizesObjectValues()
    {
        $obj = new \stdClass();
        $obj->sanitizeMe = '<script />';

        $sanitized = new \stdClass();
        $sanitized->sanitizeMe = '&lt;script /&gt;';

        $this->assertEquals($sanitized, SanitizeHelper::recursiveHtmlSpecialChars($obj));
    }

    #[TestWith(['', ''])]
    #[TestWith(['"test" in some.var', '"test" in some.var'])]
    #[TestWith(['some.craft = "test"', 'some.craft = "test"'])]
    #[TestWith(['craft.submissions', 'submissions'])]
    #[TestWith(['some.craft = "test" and craft.submit', 'some.craft = "test"craft.submit'])]
    #[TestWith(['some.craft = "test"|test(craft.submit)', 'some.craft = "test"|testsubmit)'])]
    #[TestWith(['some.craft = "test"|craft', 'some.craft = "test"'])]
    public function testSanitize($condition, $expected)
    {
        $this->assertSame($expected, SanitizeHelper::cleanUpTwigCondition($condition));
    }
}
