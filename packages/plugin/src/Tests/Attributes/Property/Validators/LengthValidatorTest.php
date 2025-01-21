<?php

namespace Solspace\Freeform\Tests\Attributes\Property\Validators;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Validators\Length;

#[CoversClass(Length::class)]
class LengthValidatorTest extends TestCase
{
    public function testValidOnSameLength()
    {
        $validator = new Length(100);

        $value = str_repeat('.', 100);

        $result = $validator->validate($value);

        $this->assertEmpty($result);
    }

    public function testValidOnSmallerLength()
    {
        $validator = new Length(100);

        $value = str_repeat('.', 99);

        $result = $validator->validate($value);

        $this->assertEmpty($result);
    }

    public function testInvalidOnLargerLength()
    {
        $validator = new Length(100);

        $value = str_repeat('.', 101);

        $result = $validator->validate($value);

        $this->assertSame(['Value contains 101 characters, 100 allowed.'], $result);
    }

    public function testCustomErrorMessage()
    {
        $validator = new Length(
            100,
            'This is max {max}, This is current {current}, this is max {max}'
        );

        $value = str_repeat('.', 101);

        $result = $validator->validate($value);

        $this->assertSame(
            ['This is max 100, This is current 101, this is max 100'],
            $result
        );
    }

    public function testDefaultsTo255()
    {
        $validator = new Length();

        $value = str_repeat('.', 256);

        $result = $validator->validate($value);

        $this->assertSame(['Value contains 256 characters, 255 allowed.'], $result);
    }
}
