<?php

namespace Solspace\Freeform\Tests\Bundles\Attributes\Property;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Attributes\Property\DefaultValue;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Implementations\Options\OptionCollection;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Attributes\Property\Middleware;
use Solspace\Freeform\Attributes\Property\TransformerInterface;
use Solspace\Freeform\Attributes\Property\ValueTransformer;
use Solspace\Freeform\Attributes\Property\VisibilityFilter;
use Solspace\Freeform\Bundles\Attributes\Property\PropertyProvider;
use Solspace\Freeform\Bundles\Fields\ImplementationProvider;
use Solspace\Freeform\Bundles\Form\Limiting\LimitedUsers\LimitedUserChecker;
use Solspace\Freeform\Bundles\Settings\DefaultsProvider;
use yii\di\Container;

#[CoversClass(PropertyProvider::class)]
class PropertyProviderTest extends TestCase
{
    private PropertyProvider $provider;

    protected function setUp(): void
    {
        $mockContainer = $this->createMock(Container::class);
        $mockContainer->method('get')->willReturn(new TestTransformer());

        $mockImplementationProvider = $this->createMock(ImplementationProvider::class);

        $mockDefaultsProvider = $this->createMock(DefaultsProvider::class);
        $mockDefaultsProvider
            ->expects($this->once())
            ->method('getValue')
            ->with($this->equalTo('settings.default.value'))
            ->willReturn('pulled from defaults')
        ;

        $mockLimitationChecker = $this->createMock(LimitedUserChecker::class);

        $this->provider = $this
            ->getMockBuilder(PropertyProvider::class)
            ->setConstructorArgs([
                $mockContainer,
                $mockImplementationProvider,
                $mockDefaultsProvider,
                $mockLimitationChecker,
            ])
            ->onlyMethods(['getPluginEdition'])
            ->getMock()
        ;

        $this->provider
            ->method('getPluginEdition')
            ->willReturn('lite')
        ;
    }

    public function testSetObjectProperties()
    {
        $object = new TestAttributesClass();

        $values = [
            'id' => 22,
            'stringValue' => [7, 8, 'nine'],
        ];

        $this->provider->setObjectProperties($object, $values);

        $this->assertEquals('7,8,nine', $object->getStringValue());
        $this->assertEquals(22, $object->getId());
    }

    #[DataProvider('propertyDataProvider')]
    public function testGetEditableProperties(array $checklist)
    {
        $handle = $checklist['handle'];

        $editableProperties = $this->provider->getEditableProperties(TestAttributesClass::class);

        foreach ($checklist as $key => $expectedValue) {
            $actualValue = $editableProperties->get($handle)->{$key};

            try {
                $message = \sprintf(
                    "Property `%s` has a mismatched `%s` value of `%s` (expected '%s')",
                    $handle,
                    $key,
                    json_encode($actualValue),
                    json_encode($expectedValue)
                );
            } catch (\Exception $e) {
                $message = $e->getMessage();
            }

            $this->assertEquals(
                $expectedValue,
                $actualValue,
                $message
            );
        }
    }

    public static function propertyDataProvider(): array
    {
        return [
            [[
                'type' => 'string',
                'handle' => 'defaultValue',
                'label' => 'Default Value',
                'instructions' => null,
                'order' => 1,
                'value' => 'pulled from defaults',
                'placeholder' => null,
                'section' => null,
                'flags' => [],
                'visibilityFilters' => [],
                'middleware' => [],
                'required' => false,
            ]],
            [[
                'type' => 'table',
                'handle' => 'stringValue',
                'label' => 'String Value',
                'instructions' => null,
                'order' => 2,
                'value' => ['one', 'two', 'three'],
                'placeholder' => null,
                'section' => null,
                'options' => null,
                'flags' => [],
                'visibilityFilters' => [],
                'middleware' => [],
                'required' => false,
            ]],
            [[
                'type' => 'hidden',
                'editions' => ['pro'],
                'handle' => 'optionalInt',
                'label' => 'Optional Integer',
                'instructions' => null,
                'order' => 3,
                'value' => null,
                'placeholder' => 'placeholder',
                'section' => null,
                'flags' => [],
                'visibilityFilters' => [],
                'middleware' => [],
                'required' => false,
            ]],
            [[
                'type' => 'select',
                'editions' => ['pro', 'lite'],
                'handle' => 'boolWithDefaultTrue',
                'label' => 'Bool With Default True',
                'instructions' => 'instructions',
                'order' => 99,
                'value' => true,
                'placeholder' => null,
                'section' => null,
                'options' => (new OptionCollection())
                    ->add('one', 'One')
                    ->add('two', 'Two')
                    ->add('three', 'Three'),
                'flags' => [],
                'visibilityFilters' => [],
                'middleware' => [],
                'required' => false,
            ]],
            [[
                'type' => 'select',
                'handle' => 'propWithMiddleware',
                'label' => 'Prop With Middleware',
                'instructions' => null,
                'order' => 4,
                'value' => null,
                'placeholder' => null,
                'section' => null,
                'options' => (new OptionCollection())
                    ->add(0, 'one')
                    ->add(1, 'two')
                    ->add(2, 'three'),
                'flags' => [],
                'visibilityFilters' => [],
                'middleware' => [
                    new Middleware('test', ['arg 1', 2, true]),
                    new Middleware('another one'),
                ],
                'required' => false,
            ]],
            [[
                'type' => 'select',
                'handle' => 'propWithFlags',
                'label' => 'Prop With Flags',
                'instructions' => null,
                'order' => 5,
                'value' => null,
                'placeholder' => null,
                'section' => null,
                'options' => (new OptionCollection())
                    ->add('one', 'One')
                    ->add('two', 'Two')
                    ->add('three', 'Three'),
                'flags' => [new Flag('test-flag'), new Flag('another-flag')],
                'visibilityFilters' => [],
                'middleware' => [],
                'required' => false,
            ]],
            [[
                'type' => 'string',
                'handle' => 'propWithFilters',
                'label' => 'Prop With Filters',
                'instructions' => null,
                'order' => 6,
                'value' => null,
                'placeholder' => null,
                'section' => null,
                'flags' => [],
                'visibilityFilters' => [
                    new VisibilityFilter('test-filter'),
                    new VisibilityFilter('second-test-filter'),
                    new VisibilityFilter('third-test-filter'),
                ],
                'middleware' => [],
                'required' => false,
            ]],
        ];
    }
}

class TestTransformer implements TransformerInterface
{
    public function transform(mixed $value): string
    {
        return implode(',', $value);
    }

    public function reverseTransform($value): array
    {
        if (\is_string($value)) {
            return explode(',', $value);
        }

        return $value;
    }
}

class TestAttributesClass
{
    private int $id;

    #[DefaultValue('settings.default.value')]
    #[Input\Text]
    private string $defaultValue;

    #[ValueTransformer(TestTransformer::class)]
    #[Input\Table(
        value: ['one', 'two', 'three'],
    )]
    private string $stringValue;

    #[Edition('pro')]
    #[Input\Integer(
        'Optional Integer',
        placeholder: 'placeholder',
    )]
    private ?int $optionalInt;

    #[Edition('pro')]
    #[Edition('lite')]
    #[Input\Select(
        instructions: 'instructions',
        order: 99,
        options: ['one' => 'One', 'two' => 'Two', 'three' => 'Three'],
    )]
    private bool $boolWithDefaultTrue = true;

    #[Input\Select(
        options: ['one', 'two', 'three'],
    )]
    #[Middleware('test', ['arg 1', 2, true])]
    #[Middleware('another one')]
    private string $propWithMiddleware;

    #[Input\Select(
        options: [
            ['value' => 'one', 'label' => 'One'],
            ['value' => 'two', 'label' => 'Two'],
            ['value' => 'three', 'label' => 'Three'],
        ],
    )]
    #[Flag('test-flag')]
    #[Flag('another-flag')]
    private string $propWithFlags;

    #[Input\Text]
    #[VisibilityFilter('test-filter')]
    #[VisibilityFilter('second-test-filter')]
    #[VisibilityFilter('third-test-filter')]
    private string $propWithFilters;

    public function getId(): int
    {
        return $this->id;
    }

    public function getStringValue(): string
    {
        return $this->stringValue;
    }
}
