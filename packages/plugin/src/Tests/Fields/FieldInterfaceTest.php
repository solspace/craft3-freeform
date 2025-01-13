<?php

namespace Solspace\Freeform\Tests\Fields;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Fields\Implementations\DropdownField;
use Solspace\Freeform\Form\Form;

#[CoversClass(DropdownField::class)]
class FieldInterfaceTest extends TestCase
{
    public function testImplementations()
    {
        $formMock = $this->createMock(Form::class);
        $field = new DropdownField($formMock);

        $this->assertTrue($field->implements('options'));
        $this->assertTrue($field->implements('generatedOptions'));
        $this->assertTrue($field->implements('nothing', 'nothing else', 'defaultValue'));
        $this->assertFalse($field->implements('foobar'));
        $this->assertFalse($field->implements('foobar', 'baz'));
    }
}
