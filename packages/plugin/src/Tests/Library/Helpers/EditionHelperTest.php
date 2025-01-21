<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\EditionHelper;

#[CoversClass(EditionHelper::class)]
class EditionHelperTest extends TestCase
{
    public function testIsAtLeast()
    {
        $helper = new EditionHelper('pro', ['lite', 'plus', 'pro', 'ultra']);

        $this->assertTrue($helper->isAtLeast('lite'));
        $this->assertTrue($helper->isAtLeast('plus'));
        $this->assertTrue($helper->isAtLeast('pro'));
        $this->assertFalse($helper->isAtLeast('ultra'));
    }

    public function testIsAtMost()
    {
        $helper = new EditionHelper('pro', ['lite', 'plus', 'pro', 'ultra']);

        $this->assertFalse($helper->isAtMost('lite'));
        $this->assertFalse($helper->isAtMost('plus'));
        $this->assertTrue($helper->isAtMost('pro'));
        $this->assertTrue($helper->isAtMost('ultra'));
    }

    public function testIs()
    {
        $helper = new EditionHelper('pro', ['lite', 'plus', 'pro', 'ultra']);

        $this->assertFalse($helper->is('lite'));
        $this->assertFalse($helper->is('plus'));
        $this->assertTrue($helper->is('pro'));
        $this->assertFalse($helper->is('ultra'));
    }

    public function testNonExistingEdition()
    {
        $helper = new EditionHelper('non-existant', ['lite', 'plus', 'pro', 'ultra']);

        $this->assertTrue($helper->is('non-existant'));
        $this->assertFalse($helper->isAtLeast('lite'));
        $this->assertFalse($helper->isAtMost('ultra'));
    }

    public function testIsBelow()
    {
        $helper = new EditionHelper('pro', ['lite', 'plus', 'pro', 'ultra']);

        $this->assertFalse($helper->isBelow('lite'));
        $this->assertFalse($helper->isBelow('plus'));
        $this->assertFalse($helper->isBelow('pro'));
        $this->assertTrue($helper->isBelow('ultra'));
    }
}
