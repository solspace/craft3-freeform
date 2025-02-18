<?php

namespace Solspace\Freeform\Tests\Library\Export;

use PHPUnit\Framework\Attributes\CoversClass;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\TextareaField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;
use Solspace\Freeform\Library\DataObjects\ExportSettings;

#[CoversClass(ExportCsv::class)]
class ExportCsvTest extends BaseExportTestingCase
{
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

        $expected = <<<'EXPECTED'
            ID,"Date Created"
            1,"2019-01-01 08:00:00"
            2,"2019-01-01 09:20:00"

            EXPECTED;

        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors);
        $exporter->export($this->resourceMock);
        $this->assertSame($expected, $this->getOutput());
    }

    public function testUnusedDescriptors()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('title', 'Title', false))
            ->add(new FieldDescriptor('dateCreated', 'Date Created'))
            ->add(new FieldDescriptor('text', 'Text', false))
        ;

        $this->generateSubmissions([
            ['id' => 1, 'title' => 'title', 'dateCreated' => new \DateTime('2019-01-01 08:00:00'), 'text' => 'text'],
            ['id' => 2, 'title' => 'title', 'dateCreated' => new \DateTime('2019-01-01 09:20:00'), 'text' => 'text'],
        ]);

        $expected = <<<'EXPECTED'
            ID,"Date Created"
            1,"2019-01-01 08:00:00"
            2,"2019-01-01 09:20:00"

            EXPECTED;

        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors);
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

        $this->formMock->method('get')
            ->willReturnCallback(
                fn (string $handle) => match ($handle) {
                    'table1' => $this->generateField(
                        TableField::class,
                        [
                            'getTableLayout' => new TableLayout([
                                ['label' => 'T1C1'],
                                ['label' => 'T1C2'],
                                ['label' => 'T1C3'],
                            ]),
                            'getLabel' => 'Table One',
                            'getHandle' => 'table1',
                            'getValue' => [['one', 'two', 'three'], ['four', 'five', ''], ['', 'six', '']],
                        ]
                    ),
                    'table2' => $this->generateField(
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
                            'getValue' => [['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'], ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5']],
                        ]
                    ),
                    'firstName' => $this->generateField(
                        TextField::class,
                        [
                            'getLabel' => 'First Name',
                            'getHandle' => 'firstName',
                            'getValue' => 'Some Name',
                        ]
                    ),
                    default => null,
                }
            )
        ;

        $this->generateSubmissions([
            [
                'id' => 1,
                'table1' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->formMock->get('table1')->getTableLayout(),
                        'getValue' => [['one', 'two', 'three'], ['four', 'five', ''], ['', 'six', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getValue' => 'Some Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->formMock->get('table2')->getTableLayout(),
                        'getValue' => [['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'], ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5']],
                    ]
                ),
            ],
            [
                'id' => 2,
                'table1' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->formMock->get('table1')->getTableLayout(),
                        'getValue' => [['some', 'value', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getValue' => 'Other Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getTableLayout' => $this->formMock->get('table2')->getTableLayout(),
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

        $exporter = new ExportCsv(
            $this->formMock,
            $this->queryMock,
            $descriptors,
        );
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
                    ['getValue' => "some text\ncontaining\nnewlines"],
                ),
            ],
            [
                'id' => 2,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValue' => "other text\ncontaining\n\n\nnewlines"],
                ),
            ],
        ]);

        $expected = <<<'EXPECTED'
            ID,Textarea
            1,"some text containing newlines"
            2,"other text containing newlines"

            EXPECTED;

        $settings = (new ExportSettings())->setRemoveNewlines(true);
        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors, $settings);
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
                    ['getValue' => "some text\ncontaining\nnewlines"],
                ),
            ],
            [
                'id' => 2,
                'textarea' => $this->generateField(
                    TextareaField::class,
                    ['getValue' => "other text\ncontaining\n\n\nnewlines"],
                ),
            ],
        ]);

        $expected = <<<'EXPECTED'
            ID,Textarea
            1,"some text
            containing
            newlines"
            2,"other text
            containing


            newlines"

            EXPECTED;

        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors);
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
                    ['getValue' => 'some text'],
                ),
            ],
            [
                'id' => 2,
                'texty' => $this->generateField(
                    TextareaField::class,
                    ['getValue' => 'other text'],
                ),
            ],
        ]);

        $expected = <<<'EXPECTED'
            id,texty
            1,"some text"
            2,"other text"

            EXPECTED;

        $settings = (new ExportSettings())->setHandlesAsNames(true);
        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors, $settings);
        $exporter->export($this->resourceMock);
        $this->assertSame($expected, $this->getOutput());
    }
}
