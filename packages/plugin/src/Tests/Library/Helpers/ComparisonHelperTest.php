<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\ComparisonHelper;

/**
 * @internal
 */
#[CoversNothing]
class ComparisonHelperTest extends TestCase
{
    #[TestWith(['(so$^me@*.com', '(so$^me@gmail.com', false])]
    #[TestWith(['some*string', 'some  string', true])]
    #[TestWith(['viagr*', 'viagra very more text', true])]
    #[TestWith(['viagr*', 'some viagra1! text', true])]
    #[TestWith(['viagr*', 'shviagra1!', false])]
    #[TestWith(['viagr*', 'this long string contains viagra1! in it', true])]
    #[TestWith(['viagr*', 'sviagra', false])]
    #[TestWith(['vi*ra', 'viagra', true])]
    #[TestWith(['vi*ra', 'viagarana ra', true])]
    #[TestWith(['some@*.com', 'some@gmail.com', true])]
    #[TestWith(['some@*.com', 'some@hotmail.com', true])]
    #[TestWith(['some@*.com', 'some@gmail.ru', false])]
    #[TestWith(['[some@*.com', '[some@gmail.com', false])]
    #[TestWith(['[some@*.com', 'some@gmail.com', false])]
    #[TestWith(['"Beautiful girls"', 'beautiful', false])]
    #[TestWith(['"Beautiful girls"', 'girls', false])]
    #[TestWith(['"Beautiful girls"', 'beautiful girls', true])]
    #[TestWith(['"Beautiful girls"', 'flowers are beautiful', false])]
    #[TestWith(['"Beautiful girls"', 'beautiful girls', true])]
    #[TestWith(['"Beautiful girls"', 'too many beautiful girls', true])]
    #[TestWith(['"Beautiful girls"', 'are there any beautiful girls in here', true])]
    #[TestWith(['*@mail.me', 'some@mail.me', true])]
    #[TestWith(['some@*.me', 'some@mail.me', true])]
    #[TestWith(['some@mail.*', 'some@mail.me', true])]
    #[TestWith(['+974', '974', true])]
    #[TestWith(['+974', '+974', true])]
    #[TestWith(['b*brides', 'bestbrides', true])]
    #[TestWith(['*charming*', 'charmingdate', true])]
    public function testTextMatchesWildcardPattern(string $pattern, string $string, bool $expectedResult)
    {
        $result = ComparisonHelper::stringContainsWildcardKeyword($pattern, $string);

        $this->assertSame(
            $expectedResult,
            $result,
            \sprintf(
                'Pattern "%s" returns "%s" for "%s". Expected: "%s"',
                $pattern,
                $result ? 'true' : 'false',
                $string,
                $expectedResult ? 'true' : 'false'
            )
        );
    }

    #[TestWith(['(so$^me@*.com', '(so$^me@gmail.com', true])]
    #[TestWith(['some*string', 'some  string', true])]
    #[TestWith(['viagr*', 'viagra very more text', true])]
    #[TestWith(['viagr*', 'viagra1! text', true])]
    #[TestWith(['viagr*', 'shviagra1!', false])]
    #[TestWith(['viagr*', 'this long string contains viagra1! in it', false])]
    #[TestWith(['viagr*', 'sviagra', false])]
    #[TestWith(['vi*ra', 'viagra', true])]
    #[TestWith(['vi*ra', 'viagarana ra', true])]
    #[TestWith(['some@*.com', 'some@gmail.com', true])]
    #[TestWith(['some@*.com', 'some@hotmail.com', true])]
    #[TestWith(['some@*.com', 'some@gmail.ru', false])]
    #[TestWith(['[some@*.com', '[some@gmail.com', true])]
    #[TestWith(['[some@*.com', 'some@gmail.com', false])]
    public function testWordMatchesWildcardPattern(string $pattern, string $string, bool $expectedResult)
    {
        $result = ComparisonHelper::stringMatchesWildcard($pattern, $string);

        $this->assertSame(
            $expectedResult,
            $result,
            \sprintf(
                'Pattern "%s" returns "%s" for "%s". Expected: "%s"',
                $pattern,
                $result ? 'true' : 'false',
                $string,
                $expectedResult ? 'true' : 'false'
            )
        );
    }
}
