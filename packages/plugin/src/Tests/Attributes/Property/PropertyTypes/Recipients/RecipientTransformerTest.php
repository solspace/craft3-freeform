<?php

namespace Solspace\Freeform\Tests\Attributes\Property\PropertyTypes\Recipients;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Implementations\Notifications\Recipients\RecipientTransformer;
use Solspace\Freeform\Notifications\Components\Recipients\Recipient;
use Solspace\Freeform\Notifications\Components\Recipients\RecipientCollection;

#[CoversClass(RecipientTransformer::class)]
class RecipientTransformerTest extends TestCase
{
    public function testTransformsArrayOfRecipientsIntoCollection()
    {
        $input = [
            ['email' => 'test1@solspace.com', 'name' => 'Some Guy'],
            ['email' => 'test2@solspace.com', 'name' => 'Some Girl'],
        ];

        $transformer = new RecipientTransformer();
        $result = $transformer->transform($input);

        $this->assertInstanceOf(RecipientCollection::class, $result);
        $this->assertCount(2, $result);

        $first = $result[0];
        $second = $result[1];

        $this->assertEquals('test1@solspace.com', $first->getEmail());
        $this->assertEquals('Some Guy', $first->getName());

        $this->assertEquals('test2@solspace.com', $second->getEmail());
        $this->assertEquals('Some Girl', $second->getName());
    }

    public function testReverseTransform()
    {
        $input = new RecipientCollection();
        $input
            ->add(new Recipient('test1@solspace.com', 'Some Guy'))
            ->add(new Recipient('test2@solspace.com', 'Some Girl'))
        ;

        $transformer = new RecipientTransformer();

        $result = $transformer->reverseTransform($input);

        $this->assertCount(2, $result);

        $this->assertEquals('test1@solspace.com', $result[0]['email']);
        $this->assertEquals('Some Guy', $result[0]['name']);

        $this->assertEquals('test2@solspace.com', $result[1]['email']);
        $this->assertEquals('Some Girl', $result[1]['name']);
    }
}
