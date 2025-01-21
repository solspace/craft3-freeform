<?php

namespace Solspace\Freeform\Tests\Library\DataObjects;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\DataObjects\Relations;

#[CoversClass(Relations::class)]
class RelationsTest extends TestCase
{
    public function testGetRelationshipWithArrayOfIds()
    {
        $relations = new Relations(['handle' => [1, 2, 3]]);

        $this->assertCount(3, $relations->getRelationships());
        $this->assertSame(1, $relations->getRelationships()[0]->getElementId());
        $this->assertSame(2, $relations->getRelationships()[1]->getElementId());
        $this->assertSame(3, $relations->getRelationships()[2]->getElementId());
    }

    public function testGetRelationshipWithStringId()
    {
        $relations = new Relations(['handle' => '55']);

        $this->assertCount(1, $relations->getRelationships());
        $this->assertSame(55, $relations->getRelationships()[0]->getElementId());
    }

    public function testGetRelationshipWithIntId()
    {
        $relations = new Relations(['handle' => 55]);

        $this->assertCount(1, $relations->getRelationships());
        $this->assertSame(55, $relations->getRelationships()[0]->getElementId());
    }

    public function testGetMultipleRelationHandles()
    {
        $relations = new Relations(['handleOne' => 1, 'handleTwo' => 2]);

        $this->assertCount(2, $relations->getRelationships());
        $this->assertSame('handleOne', $relations->getRelationships()[0]->getFieldHandle());
        $this->assertSame('handleTwo', $relations->getRelationships()[1]->getFieldHandle());
    }
}
