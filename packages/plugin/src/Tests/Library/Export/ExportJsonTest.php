<?php

namespace Solspace\Freeform\Tests\Library\Export;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Bundles\Export\Implementations\Json\ExportJson;
use Solspace\Freeform\Fields\Implementations\Pro\TableField;
use Solspace\Freeform\Fields\Implementations\TextField;
use Solspace\Freeform\Fields\Properties\Table\TableLayout;
use Solspace\Freeform\Form\Form;

/**
 * @internal
 *
 * @coversNothing
 */
class ExportJsonTest extends TestCase
{
    /** @var Form|MockObject */
    private $formMock;

    /** @var MockObject|TableField */
    private $tableField1Mock;

    /** @var MockObject|TableField */
    private $tableField2Mock;

    /** @var MockObject|TextField */
    private $textFieldMock;

    protected function setUp(): void
    {
        $tableLayout1 = new TableLayout([
            ['label' => 'T1C1'],
            ['label' => 'T1C2'],
            ['label' => 'T1C3'],
        ]);

        $tableLayout2 = new TableLayout([
            ['label' => 'T2C1'],
            ['label' => 'T2C2'],
            ['label' => 'T2C3'],
            ['label' => 'T2C4'],
            ['label' => 'T2C5'],
        ]);

        $this->tableField1Mock = $this->createMock(TableField::class);
        $this->tableField1Mock
            ->method('getTableLayout')
            ->willReturn($tableLayout1)
        ;

        $this->tableField1Mock
            ->method('getHandle')
            ->willReturn('table1')
        ;

        $this->tableField2Mock = $this->createMock(TableField::class);
        $this->tableField2Mock
            ->method('getTableLayout')
            ->willReturn($tableLayout2)
        ;

        $this->tableField2Mock
            ->method('getHandle')
            ->willReturn('table2')
        ;

        $this->textFieldMock = $this->createMock(TextField::class);
        $this->textFieldMock
            ->method('getLabel')
            ->willReturn('First Name')
        ;

        $this->textFieldMock
            ->method('getHandle')
            ->willReturn('firstName')
        ;

        $this->formMock = $this->createMock(Form::class);
        $this->formMock
            ->method('get')
            ->willReturnOnConsecutiveCalls(
                $this->tableField1Mock,
                $this->textFieldMock,
                $this->tableField2Mock,
                $this->tableField1Mock,
                $this->textFieldMock,
                $this->tableField2Mock
            )
        ;
    }

    public function testEmptyExport()
    {
        $exporter = new ExportJson($this->formMock, []);

        $this->assertSame($exporter->export(), '[]');
    }

    public function testExportBasicRows()
    {
        $exporter = new ExportJson($this->formMock, [
            ['id' => 1, 'dateCreated' => '2019-01-01 08:00:00'],
            ['id' => 2, 'dateCreated' => '2019-01-01 09:20:00'],
        ]);

        $expected = <<<'EXPECTED'
            [
                {
                    "id": 1,
                    "dateCreated": "2019-01-01 08:00:00"
                },
                {
                    "id": 2,
                    "dateCreated": "2019-01-01 09:20:00"
                }
            ]
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }

    public function testExportTableRows()
    {
        $table1row1 = json_encode([
            ['one', 'two', 'three'],
            ['four', 'five', ''],
            ['', 'six', ''],
        ]);

        $table1row2 = json_encode([
            ['some', 'value', ''],
        ]);

        $table2row1 = json_encode([
            ['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'],
            ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5'],
        ]);

        $table2row2 = json_encode([
            ['r1c1', 'r1c2', 'r1c3', 'r1c4', 'r1c5'],
            ['r2c1', 'r2c2', 'r2c3', 'r2c4', 'r2c5'],
            ['r3c1', 'r3c2', 'r3c3', 'r3c4', 'r3c5'],
            ['r4c1', 'r4c2', 'r4c3', 'r4c4', 'r4c5'],
            ['r5c1', 'r5c2', 'r5c3', 'r5c4', 'r5c5'],
        ]);

        $exporter = new ExportJson($this->formMock, [
            ['id' => 1, 'table1' => $table1row1, 'firstName' => 'Some Name', 'table2' => $table2row1],
            ['id' => 2, 'table1' => $table1row2, 'firstName' => 'Other Name', 'table2' => $table2row2],
        ]);

        $expected = <<<'EXPECTED'
            [
                {
                    "id": 1,
                    "table1": [
                        [
                            "one",
                            "two",
                            "three"
                        ],
                        [
                            "four",
                            "five",
                            ""
                        ],
                        [
                            "",
                            "six",
                            ""
                        ]
                    ],
                    "firstName": "Some Name",
                    "table2": [
                        [
                            "r1c1",
                            "r1c2",
                            "r1c3",
                            "r1c4",
                            "r1c5"
                        ],
                        [
                            "r2c1",
                            "r2c2",
                            "r2c3",
                            "r2c4",
                            "r2c5"
                        ]
                    ]
                },
                {
                    "id": 2,
                    "table1": [
                        [
                            "some",
                            "value",
                            ""
                        ]
                    ],
                    "firstName": "Other Name",
                    "table2": [
                        [
                            "r1c1",
                            "r1c2",
                            "r1c3",
                            "r1c4",
                            "r1c5"
                        ],
                        [
                            "r2c1",
                            "r2c2",
                            "r2c3",
                            "r2c4",
                            "r2c5"
                        ],
                        [
                            "r3c1",
                            "r3c2",
                            "r3c3",
                            "r3c4",
                            "r3c5"
                        ],
                        [
                            "r4c1",
                            "r4c2",
                            "r4c3",
                            "r4c4",
                            "r4c5"
                        ],
                        [
                            "r5c1",
                            "r5c2",
                            "r5c3",
                            "r5c4",
                            "r5c5"
                        ]
                    ]
                }
            ]
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }
}
