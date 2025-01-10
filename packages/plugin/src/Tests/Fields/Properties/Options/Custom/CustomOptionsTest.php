<?php

namespace Solspace\Freeform\Tests\Fields\Properties\Options\Custom;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionsTransformer;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Fields\Properties\Options\Custom\Custom;

#[CoversClass(OptionsTransformer::class)]
class CustomOptionsTest extends TestCase
{
    private PropertyProvider $propertyProvider;

    protected function setUp(): void
    {
        $this->propertyProvider = $this->createMock(PropertyProvider::class);
    }

    public function testTransform()
    {
        $value = [
            'source' => 'customOptions',
            'useCustomValues' => true,
            'options' => [
                ['label' => 'Checked option', 'value' => 'checkedOption'],
                ['label' => 'Unchecked option', 'value' => 'uncheckedOption'],
            ],
        ];

        $output = (new OptionsTransformer($this->propertyProvider))->transform($value);

        $expected = new Custom([
            'useCustomValues' => true,
            'options' => [
                ['label' => 'Checked option', 'value' => 'checkedOption'],
                ['label' => 'Unchecked option', 'value' => 'uncheckedOption'],
            ],
        ]);

        $this->assertEquals($expected, $output);
    }

    public function testReverseTransform()
    {
        $value = new Custom([
            'useCustomValues' => true,
            'options' => [
                ['label' => 'Checked option', 'value' => 'checkedOption'],
                ['label' => 'Unchecked option', 'value' => 'uncheckedOption'],
            ],
        ]);

        $output = (new OptionsTransformer($this->propertyProvider))->reverseTransform($value);

        $expected = [
            'source' => 'custom',
            'useCustomValues' => true,
            'options' => [
                ['label' => 'Checked option', 'value' => 'checkedOption'],
                ['label' => 'Unchecked option', 'value' => 'uncheckedOption'],
            ],
        ];

        $this->assertEquals($expected, $output);
    }
}
