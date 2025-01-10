<?php

namespace Solspace\Freeform\Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

#[CoversFunction('version_compare')]
class VersionTest extends TestCase
{
    #[TestWith(['1.8.2', '<'])]
    #[TestWith(['2.0.0-dev', '='])]
    #[TestWith(['2.0.0-alpha.1', '>'])]
    #[TestWith(['2.0.0-beta.1', '>'])]
    public function testVersions(string $version, string $operator)
    {
        $this->assertTrue(version_compare($version, '2.0.0-dev', $operator));
    }

    public function testCraft31BetaCheck()
    {
        $this->assertTrue(version_compare('3.1.0-beta.4', '3.1', '>='));
    }

    public function testCraft31PreBetaCheck()
    {
        $this->assertTrue(version_compare('3.0.40', '3.1', '<'));
    }
}
