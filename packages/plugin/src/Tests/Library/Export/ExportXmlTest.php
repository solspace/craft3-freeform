<?php

namespace Solspace\Freeform\Tests\Library\Export;

use PHPUnit\Framework\Attributes\CoversClass;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Xml\ExportXml;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;

#[CoversClass(ExportXml::class)]
class ExportXmlTest extends BaseExportTestingCase
{
    public function testEmptyExport()
    {
        $this->queryMock->method('batch')->willReturn([]);

        $exporter = new ExportXml($this->formMock, $this->queryMock, new FieldDescriptorCollection());
        $exporter->export($this->resourceMock);
        $this->assertSame('<?xml version="1.0" encoding="UTF-8"?>'."\n<root/>\n", $this->getOutput());
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
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
             <submission>
              <id label="ID">1</id>
              <dateCreated label="Date Created">2019-01-01 08:00:00</dateCreated>
             </submission>
             <submission>
              <id label="ID">2</id>
              <dateCreated label="Date Created">2019-01-01 09:20:00</dateCreated>
             </submission>
            </root>

            EXPECTED;

        $exporter = new ExportXml($this->formMock, $this->queryMock, $descriptors);
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
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
             <submission>
              <id label="ID">1</id>
              <dateCreated label="Date Created">2019-01-01 08:00:00</dateCreated>
             </submission>
             <submission>
              <id label="ID">2</id>
              <dateCreated label="Date Created">2019-01-01 09:20:00</dateCreated>
             </submission>
            </root>

            EXPECTED;

        $exporter = new ExportXml($this->formMock, $this->queryMock, $descriptors);
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
                        ]
                    ),
                    'firstName' => $this->generateField(
                        TextField::class,
                        [
                            'getLabel' => 'First Name',
                            'getHandle' => 'firstName',
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
                        'getHandle' => 'table1',
                        'getTableLayout' => $this->formMock->get('table1')->getTableLayout(),
                        'getValue' => [['one', 'two', 'three'], ['four', 'five', ''], ['', 'six', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getHandle' => 'firstName', 'getValue' => 'Some Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getHandle' => 'table2',
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
                        'getHandle' => 'table1',
                        'getTableLayout' => $this->formMock->get('table1')->getTableLayout(),
                        'getValue' => [['some', 'value', '']],
                    ]
                ),
                'firstName' => $this->generateField(
                    TextField::class,
                    ['getHandle' => 'firstName', 'getValue' => 'Other Name']
                ),
                'table2' => $this->generateField(
                    TableField::class,
                    [
                        'getHandle' => 'table2',
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
            <?xml version="1.0" encoding="UTF-8"?>
            <root>
             <submission>
              <id label="ID">1</id>
              <table1 label="Table Field">
               <row>
                <column label="T1C1">one</column>
                <column label="T1C2">two</column>
                <column label="T1C3">three</column>
               </row>
               <row>
                <column label="T1C1">four</column>
                <column label="T1C2">five</column>
                <column label="T1C3"/>
               </row>
               <row>
                <column label="T1C1"/>
                <column label="T1C2">six</column>
                <column label="T1C3"/>
               </row>
              </table1>
              <firstName label="First Name">Some Name</firstName>
              <table2 label="Table Field 2">
               <row>
                <column label="T2C1">r1c1</column>
                <column label="T2C2">r1c2</column>
                <column label="T2C3">r1c3</column>
                <column label="T2C4">r1c4</column>
                <column label="T2C5">r1c5</column>
               </row>
               <row>
                <column label="T2C1">r2c1</column>
                <column label="T2C2">r2c2</column>
                <column label="T2C3">r2c3</column>
                <column label="T2C4">r2c4</column>
                <column label="T2C5">r2c5</column>
               </row>
              </table2>
             </submission>
             <submission>
              <id label="ID">2</id>
              <table1 label="Table Field">
               <row>
                <column label="T1C1">some</column>
                <column label="T1C2">value</column>
                <column label="T1C3"/>
               </row>
              </table1>
              <firstName label="First Name">Other Name</firstName>
              <table2 label="Table Field 2">
               <row>
                <column label="T2C1">r1c1</column>
                <column label="T2C2">r1c2</column>
                <column label="T2C3">r1c3</column>
                <column label="T2C4">r1c4</column>
                <column label="T2C5">r1c5</column>
               </row>
               <row>
                <column label="T2C1">r2c1</column>
                <column label="T2C2">r2c2</column>
                <column label="T2C3">r2c3</column>
                <column label="T2C4">r2c4</column>
                <column label="T2C5">r2c5</column>
               </row>
               <row>
                <column label="T2C1">r3c1</column>
                <column label="T2C2">r3c2</column>
                <column label="T2C3">r3c3</column>
                <column label="T2C4">r3c4</column>
                <column label="T2C5">r3c5</column>
               </row>
               <row>
                <column label="T2C1">r4c1</column>
                <column label="T2C2">r4c2</column>
                <column label="T2C3">r4c3</column>
                <column label="T2C4">r4c4</column>
                <column label="T2C5">r4c5</column>
               </row>
               <row>
                <column label="T2C1">r5c1</column>
                <column label="T2C2">r5c2</column>
                <column label="T2C3">r5c3</column>
                <column label="T2C4">r5c4</column>
                <column label="T2C5">r5c5</column>
               </row>
              </table2>
             </submission>
            </root>

            EXPECTED;

        $exporter = new ExportXml($this->formMock, $this->queryMock, $descriptors);
        $exporter->export($this->resourceMock);
        $this->assertSame($expected, $this->getOutput());
    }
}
