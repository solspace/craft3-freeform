<?php

namespace Solspace\Freeform\Tests\Library\Export;

use craft\helpers\App;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\EventListeners\ConvertValuesToString;
use Solspace\Freeform\Bundles\Export\EventListeners\NewLineRemoval;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Library\DataObjects\ExportSettings;

/**
 * @internal
 *
 * @coversNothing
 */
class ExportCsvTest extends TestCase
{
    private Form|MockObject $formMock;
    private MockObject|SubmissionQuery $queryMock;
    private MockObject|TableField $tableField1Mock;
    private MockObject|TableField $tableField2Mock;
    private MockObject|TextField $textFieldMock;
    private $resourceMock;

    protected function setUp(): void
    {
        $this->tableField1Mock = $this->generateField(
            TableField::class,
            [
                'getTableLayout' => new TableLayout([
                    ['label' => 'T1C1'],
                    ['label' => 'T1C2'],
                    ['label' => 'T1C3'],
                ]),
                'getLabel' => 'Table One',
                'getHandle' => 'table1',
            ]
        );

        $this->tableField2Mock = $this->generateField(
            TableField::class,
            [
                'getTableLayout' => new TableLayout([
                    ['label' => 'T2C1'],
                    ['label' => 'T2C2'],
                    ['label' => 'T2C3'],
                    ['label' => 'T2C4'],
                    ['label' => 'T2C5'],
                ]),
                'getLabel' => 'Table Two',
                'getHandle' => 'table2',
            ]
        );

        $this->textFieldMock = $this->generateField(
            TextField::class,
            [
                'getLabel' => 'First Name',
                'getHandle' => 'firstName',
            ]
        );

        $this->formMock = $this->createMock(Form::class);
        $this->formMock->method('get')
            ->willReturnCallback(
                fn (string $handle) => match ($handle) {
                    'table1' => $this->tableField1Mock,
                    'table2' => $this->tableField2Mock,
                    'firstName' => $this->textFieldMock,
                    default => null,
                }
            )
        ;

        $this->queryMock = $this->createMock(SubmissionQuery::class);

        $this->resourceMock = fopen('php://memory', 'w+');
        $this->assertNotFalse($this->resourceMock);

        new ConvertValuesToString();
        new NewLineRemoval();
    }

    public function testEmptyExport()
    {
        $exporter = new ExportCsv($this->formMock, $this->queryMock, new FieldDescriptorCollection());
        $exporter->export($this->resourceMock);

        $this->assertEmpty($this->getOutput());
    }

    public function testExportBasicRows()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('dateCreated', 'Date Created'))
        ;

        $this->generateSubmissions([
            ['id' => 1, 'dateCreated' => new \DateTime('2019-01-01 08:00:00')],
            ['id' => 2, 'dateCreated' => new \DateTime('2019-01-01 09:20:00')],
        ]);

        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors);
        $expected = <<<'EXPECTED'
            ID,"Date Created"
            1,"2019-01-01 08:00:00"
            2,"2019-01-01 09:20:00"

            EXPECTED;

        $exporter->export($this->resourceMock);

        $this->assertSame($expected, $this->getOutput());
    }

    public function testExportTableRows()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('table1', 'Table Field'))
            ->add(new FieldDescriptor('firstName', 'First Name'))
            ->add(new FieldDescriptor('table2', 'Table Field 2'))
        ;

        $this->generateSubmissions([
            [
                'id' => 1,
                'table1' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->tableField1Mock->getTableLayout(),
                        'getValue' => [['one', 'two', 'three'], ['four', 'five', ''], ['', 'six', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getValueAsString' => 'Some Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->tableField2Mock->getTableLayout(),
                        'getValue' => [['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'], ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5']],
                    ]
                ),
            ],
            [
                'id' => 2,
                'table1' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->tableField1Mock->getTableLayout(),
                        'getValue' => [['some', 'value', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getValueAsString' => 'Other Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->tableField2Mock->getTableLayout(),
                        'getValue' => [
                            ['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'],
                            ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5'],
                            ['r3c1', 'r3c2', 'r3c3', 'r3c4', 'r3c5'],
                            ['r4c1', 'r4c2', 'r4c3', 'r4c4', 'r4c5'],
                            ['r5c1', 'r5c2', 'r5c3', 'r5c4', 'r5c5'],
                        ],
                    ]
                ),
            ],
        ]);

        $exporter = new ExportCsv(
            $this->formMock,
            $this->queryMock,
            $descriptors,
        );

        $expected = <<<'EXPECTED'
            ID,T1C1,T1C2,T1C3,"First Name",T2C1,T2C2,T2C3,T2C4,T2C5
            1,one,two,three,"Some Name",r1c1,r1c2,r1c3,r1c4,r1c5
            ,four,five,,,r2c1,r2c2,r2c3,r2c4,r2c5
            ,,six,,,,,,,
            2,some,value,,"Other Name",r1c1,r1c2,r1c3,r1c4,r1c5
            ,,,,,r2c1,r2c2,r2c3,r2c4,r2c5
            ,,,,,r3c1,r3c2,r3c3,r3c4,r3c5
            ,,,,,r4c1,r4c2,r4c3,r4c4,r4c5
            ,,,,,r5c1,r5c2,r5c3,r5c4,r5c5

            EXPECTED;

        $exporter->export($this->resourceMock);

        $this->assertSame($expected, $this->getOutput());
    }

    public function testExportRemoveNewlinesOn()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('textarea', 'Textarea'))
        ;

        $this->formMock->method('get')
            ->willReturnCallback(
                fn (string $handle) => match ($handle) {
                    'textarea' => $this->generateField(
                        TextareaField::class,
                        [
                            'getLabel' => 'Textarea',
                            'getHandle' => 'textarea',
                        ]
                    ),
                    default => null,
                }
            )
        ;

        $this->generateSubmissions([
            [
                'id' => 1,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => "some text\ncontaining\nnewlines"],
                ),
            ],
            [
                'id' => 2,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => "other text\ncontaining\n\n\nnewlines"],
                ),
            ],
        ]);

        $settings = new ExportSettings();
        $settings->setRemoveNewlines(true);

        $exporter = new ExportCsv(
            $this->formMock,
            $this->queryMock,
            $descriptors,
            $settings,
        );

        $expected = <<<'EXPECTED'
            ID,Textarea
            1,"some text containing newlines"
            2,"other text containing newlines"

            EXPECTED;

        $exporter->export($this->resourceMock);

        $this->assertSame($expected, $this->getOutput());
    }

    public function testExportRemoveNewlinesOff()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('textarea', 'Textarea'))
        ;

        $this->formMock->method('get')
            ->willReturnCallback(
                fn (string $handle) => match ($handle) {
                    'textarea' => $this->generateField(
                        TextareaField::class,
                        [
                            'getLabel' => 'Textarea',
                            'getHandle' => 'textarea',
                        ]
                    ),
                    default => null,
                }
            )
        ;

        $this->generateSubmissions([
            [
                'id' => 1,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => "some text\ncontaining\nnewlines"],
                ),
            ],
            [
                'id' => 2,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => "other text\ncontaining\n\n\nnewlines"],
                ),
            ],
        ]);

        $exporter = new ExportCsv(
            $this->formMock,
            $this->queryMock,
            $descriptors,
        );

        $expected = <<<'EXPECTED'
            ID,Textarea
            1,"some text
            containing
            newlines"
            2,"other text
            containing


            newlines"

            EXPECTED;

        $exporter->export($this->resourceMock);

        $this->assertSame($expected, $this->getOutput());
    }

    public function testExportHandlesAsNames()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('texty', 'Textarea'))
        ;

        $this->formMock->method('get')
            ->willReturnCallback(
                fn (string $handle) => match ($handle) {
                    'textarea' => $this->generateField(
                        TextareaField::class,
                        [
                            'getLabel' => 'Textarea',
                            'getHandle' => 'texty',
                        ]
                    ),
                    default => null,
                }
            )
        ;

        $this->generateSubmissions([
            [
                'id' => 1,
                'texty' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => 'some text'],
                ),
            ],
            [
                'id' => 2,
                'texty' => $this->generateField(
                    TextareaField::class,
                    ['getValueAsString' => 'other text'],
                ),
            ],
        ]);

        $settings = new ExportSettings();
        $settings->setHandlesAsNames(true);

        $exporter = new ExportCsv(
            $this->formMock,
            $this->queryMock,
            $descriptors,
            $settings,
        );

        $expected = <<<'EXPECTED'
            id,texty
            1,"some text"
            2,"other text"

            EXPECTED;

        $exporter->export($this->resourceMock);

        $this->assertSame($expected, $this->getOutput());
    }

    private function getOutput(): string
    {
        rewind($this->resourceMock);
        $output = stream_get_contents($this->resourceMock);
        fclose($this->resourceMock);

        return $output;
    }

    private function generateSubmissions(array $data): void
    {
        $submissions = array_map(
            function (array $row) {
                $mock = $this->createMock(Submission::class);
                App::configure($mock, $row);

                $callback = function ($fieldId) use ($row) {
                    return $row[$fieldId] ?? null;
                };

                $mock
                    ->method('__get')
                    ->willReturnCallback($callback)
                ;

                return $mock;
            },
            $data
        );

        $this
            ->queryMock
            ->method('batch')
            ->willReturn([$submissions])
        ;
    }

    private function generateField(string $class, array $stubs): FieldInterface|MockObject
    {
        $mock = $this->createMock($class);

        foreach ($stubs as $method => $value) {
            $mock
                ->method($method)
                ->willReturn($value)
            ;
        }

        return $mock;
    }
}
