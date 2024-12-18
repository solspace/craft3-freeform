<?php

namespace Solspace\Freeform\Tests\Library\Export;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Bundles\Export\Collections\FieldDescriptorCollection;
use Solspace\Freeform\Bundles\Export\Implementations\Csv\ExportCsv;
use Solspace\Freeform\Bundles\Export\Objects\FieldDescriptor;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
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
            ->method('getLabel')
            ->willReturn('Table One')
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
            ->method('getLabel')
            ->willReturn('Table Two')
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
        $this->queryMock = $this->createMock(SubmissionQuery::class);
    }

    public function testEmptyExport()
    {
        $exporter = new ExportCsv($this->formMock, $this->queryMock, new FieldDescriptorCollection());

        $this->assertEmpty($exporter->export());
    }

    public function testExportBasicRows()
    {
        $descriptors = (new FieldDescriptorCollection())
            ->add(new FieldDescriptor('id', 'ID'))
            ->add(new FieldDescriptor('dateCreated', 'Date Created'))
        ;

        $testData = [
            ['id' => 1, 'dateCreated' => new \DateTime('2019-01-01 08:00:00')],
            ['id' => 2, 'dateCreated' => new \DateTime('2019-01-01 09:20:00')],
        ];

        $submissions = [];
        foreach ($testData as $data) {
            $mock = $this->createMock(Submission::class);
            $mock->method('__get')->willReturnCallback(fn ($name) => $testData[0][$name]);

            $submissions[] = $mock;
        }

        $exporter = new ExportCsv($this->formMock, $this->queryMock, $descriptors);

        $this
            ->queryMock
            ->method('batch')
            ->willReturn($submissions)
        ;

        $expected = <<<'EXPECTED'
            "ID","Date Created"
            "1","2019-01-01 08:00:00"
            "2","2019-01-01 09:20:00"
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

        $exporter = new ExportCsv(
            $this->formMock,
            [
                ['id' => 1, 'table1' => $table1row1, 'firstName' => 'Some Name', 'table2' => $table2row1],
                ['id' => 2, 'table1' => $table1row2, 'firstName' => 'Other Name', 'table2' => $table2row2],
            ]
        );

        $expected = <<<'EXPECTED'
            "ID","T1C1","T1C2","T1C3","First Name","T2C1","T2C2","T2C3","T2C4","T2C5"
            "1","one","two","three","Some Name","r1c1","r1c2","r1c3","r1c4","r1c5"
            ,"four","five",,,"r2c1","r2c2","r2c3","r2c4","r2c5"
            ,,"six",,,,,,,
            "2","some","value",,"Other Name","r1c1","r1c2","r1c3","r1c4","r1c5"
            ,,,,,"r2c1","r2c2","r2c3","r2c4","r2c5"
            ,,,,,"r3c1","r3c2","r3c3","r3c4","r3c5"
            ,,,,,"r4c1","r4c2","r4c3","r4c4","r4c5"
            ,,,,,"r5c1","r5c2","r5c3","r5c4","r5c5"
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }

    public function testExportRemoveNewlinesOn()
    {
        $textareaFieldMock = $this->createMock(TextareaField::class);
        $textareaFieldMock
            ->method('getLabel')
            ->willReturn('Textarea')
        ;
        $textareaFieldMock
            ->method('getHandle')
            ->willReturn('textarea')
        ;

        $formMock = $this->createMock(Form::class);
        $formMock
            ->method('get')
            ->willReturn($textareaFieldMock)
        ;

        $settings = new ExportSettings();
        $settings->setRemoveNewlines(true);

        $exporter = new ExportCsv(
            $formMock,
            [
                ['id' => 1, 'textarea' => "some text\ncontaining\nnewlines"],
                ['id' => 2, 'textarea' => "other text\ncontaining\n\n\nnewlines"],
            ],
            $settings
        );

        $expected = <<<'EXPECTED'
            "ID","Textarea"
            "1","some text containing newlines"
            "2","other text containing newlines"
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }

    public function testExportRemoveNewlinesOff()
    {
        $textareaFieldMock = $this->createMock(TextareaField::class);
        $textareaFieldMock
            ->method('getLabel')
            ->willReturn('Textarea')
        ;
        $textareaFieldMock
            ->method('getHandle')
            ->willReturn('textarea')
        ;

        $formMock = $this->createMock(Form::class);
        $formMock
            ->method('get')
            ->willReturn($textareaFieldMock)
        ;

        $exporter = new ExportCsv($formMock, [
            ['id' => 1, 'textarea' => "some text\ncontaining\nnewlines"],
            ['id' => 2, 'textarea' => "other text\ncontaining\n\n\nnewlines"],
        ]);

        $expected = <<<'EXPECTED'
            "ID","Textarea"
            "1","some text
            containing
            newlines"
            "2","other text
            containing


            newlines"
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }

    public function testExportHandlesAsNames()
    {
        $textareaFieldMock = $this->createMock(TextareaField::class);
        $textareaFieldMock
            ->method('getLabel')
            ->willReturn('Textarea')
        ;
        $textareaFieldMock
            ->method('getHandle')
            ->willReturn('texty')
        ;

        $formMock = $this->createMock(Form::class);
        $formMock
            ->method('get')
            ->willReturn($textareaFieldMock)
        ;

        $exporter = new ExportCsv(
            $formMock,
            [
                ['id' => 1, 'texty' => 'some text'],
                ['id' => 2, 'texty' => 'other text'],
            ],
            (new ExportSettings())->setHandlesAsNames(true)
        );

        $expected = <<<'EXPECTED'
            "id","texty"
            "1","some text"
            "2","other text"
            EXPECTED;

        $this->assertSame($expected, $exporter->export());
    }
}
