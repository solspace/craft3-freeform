<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\StringHelper;

#[CoversClass(StringHelper::class)]
class StringHelperTest extends TestCase
{
    public function testIncrementStringWithNoNumber()
    {
        $this->assertSame(
            'test string1',
            StringHelper::incrementStringWithNumber('test string')
        );
    }

    public function testIncrementStringWithNumber()
    {
        $this->assertSame(
            'test string154',
            StringHelper::incrementStringWithNumber('test string153')
        );
    }

    public function testIncrementStringWithNoNumberSpaced()
    {
        $this->assertSame(
            'test string 1',
            StringHelper::incrementStringWithNumber('test string', true)
        );
    }

    public function testIncrementStringSpaced()
    {
        $this->assertSame(
            'test string 154',
            StringHelper::incrementStringWithNumber('test string 153', true)
        );
    }

    #[TestWith(['one two three', ['one', 'two', 'three']])]
    #[TestWith(["one\ntwo\n\rthree", ['one', 'two', 'three']])]
    #[TestWith(['one,two,three', ['one', 'two', 'three']])]
    #[TestWith(['one,two "three four"', ['one', 'two', '"three four"']])]
    #[TestWith(["one 'two three' four", ['one', "'two three'", 'four']])]
    #[TestWith(["one 'two \"three' four", ['one', '\'two "three\'', 'four']])]
    #[TestWith(['one@goog"le#.-=!?+_: test', ['one@goog"le#.-=!?+_:', 'test']])]
    #[TestWith(['one;two;three', ['one', 'two', 'three']])]
    #[TestWith(['one,two,three', ['one', 'two', 'three']])]
    #[TestWith(['one|two|three', ['one', 'two', 'three']])]
    #[TestWith(['one,| two|;three', ['one', 'two', 'three']])]
    public function testExtractSeparatedValues($input, $expected)
    {
        $result = StringHelper::extractSeparatedValues($input);
        $this->assertSame(
            $expected,
            $result,
            \sprintf("Failed to extract separated values from \"%s\"\nGot %s", $input, json_encode($result, \JSON_PRETTY_PRINT))
        );
    }

    public function testIsEnvVariable()
    {
        $this->assertTrue(StringHelper::isEnvVariable('$TEST'));
        $this->assertTrue(StringHelper::isEnvVariable('$test_VARIABLE'));
        $this->assertTrue(StringHelper::isEnvVariable('$test_variable'));
        $this->assertTrue(StringHelper::isEnvVariable('$TEST_VARIABLE'));
        $this->assertFalse(StringHelper::isEnvVariable('${TEST_VARIABLE}'));
        $this->assertFalse(StringHelper::isEnvVariable('${TEST_BEST'));
        $this->assertFalse(StringHelper::isEnvVariable('{TEST$BEST}'));
        $this->assertFalse(StringHelper::isEnvVariable('TEST$BEST'));
        $this->assertFalse(StringHelper::isEnvVariable('Not an env variable'));
        $this->assertFalse(StringHelper::isEnvVariable('!?_$_'));
        $this->assertFalse(StringHelper::isEnvVariable('!?_$_'));
    }
}
