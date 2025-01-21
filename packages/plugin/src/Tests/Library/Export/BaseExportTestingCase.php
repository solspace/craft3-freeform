<?php

namespace Solspace\Freeform\Tests\Library\Export;

use craft\helpers\App;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Bundles\Export\EventListeners\ConvertValuesToString;
use Solspace\Freeform\Bundles\Export\EventListeners\DateFormatter;
use Solspace\Freeform\Bundles\Export\EventListeners\NewLineRemoval;
use Solspace\Freeform\Elements\Db\SubmissionQuery;
use Solspace\Freeform\Elements\Submission;
use Solspace\Freeform\Fields\FieldInterface;
use Solspace\Freeform\Form\Form;

abstract class BaseExportTestingCase extends TestCase
{
    protected Form|MockObject $formMock;
    protected MockObject|SubmissionQuery $queryMock;
    protected $resourceMock;

    protected function setUp(): void
    {
        $this->formMock = $this->createMock(Form::class);
        $this->queryMock = $this->createMock(SubmissionQuery::class);

        $this->resourceMock = fopen('php://memory', 'w+');
        $this->assertNotFalse($this->resourceMock);

        new DateFormatter();
        new ConvertValuesToString();
        new NewLineRemoval();
    }

    protected function getOutput(): string
    {
        rewind($this->resourceMock);
        $output = stream_get_contents($this->resourceMock);
        fclose($this->resourceMock);

        return $output;
    }

    protected function generateSubmissions(array $data): void
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

        $this
            ->queryMock
            ->method('count')
            ->willReturn(\count($submissions))
        ;
    }

    protected function generateField(string $class, array $stubs): FieldInterface|MockObject
    {
        $mock = $this->createMock($class);

        foreach ($stubs as $method => $value) {
            $mock->method($method)->willReturn($value);
        }

        return $mock;
    }
}
