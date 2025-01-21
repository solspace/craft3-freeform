<?php

namespace Solspace\Freeform\Tests\Events\Forms;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Events\Forms\PersistFormEvent;

#[CoversClass(PersistFormEvent::class)]
class PersistFormEventTest extends TestCase
{
    public function testAddErrorsToResponse()
    {
        $event = new PersistFormEvent((object) [], null);
        $event->addErrorsToResponse('form', ['handle' => ['test'], 'name' => ['test 2', 'test 3']]);

        $this->assertEquals(
            [
                'errors' => [
                    'form' => [
                        'handle' => ['test'],
                        'name' => ['test 2', 'test 3'],
                    ],
                ],
            ],
            $event->getResponseData()
        );
    }

    public function testStatus400OnErrors()
    {
        $event = new PersistFormEvent((object) [], null);

        $this->assertNull($event->getStatus());

        $event->addErrorsToResponse('form', ['test']);

        $this->assertSame(400, $event->getStatus());
    }
}
