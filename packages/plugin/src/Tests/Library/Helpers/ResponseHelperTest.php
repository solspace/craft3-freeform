<?php

namespace Solspace\Freeform\Tests\Library\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Helpers\ResponseHelper;
use yii\web\HeaderCollection;
use yii\web\Response;

#[CoversClass(ResponseHelper::class)]
class ResponseHelperTest extends TestCase
{
    private ResponseHelper $responseHelper;
    private HeaderCollection $headerCollection;

    protected function setUp(): void
    {
        $this->headerCollection = $this->createMock(HeaderCollection::class);

        $response = $this->createMock(Response::class);
        $response->expects($this->any())->method('getHeaders')->willReturn(
            $this->headerCollection
        );

        $this->responseHelper = $this->createPartialMock(ResponseHelper::class, ['getResponse']);
        $this->responseHelper->expects($this->any())->method('getResponse')->willReturn($response);
    }

    public function testSetContentSecurityPolicy()
    {
        $matcher = $this->exactly(3);
        $this->headerCollection
            ->expects($matcher)
            ->method('set')
            ->willReturnCallback(
                function (string $key, string $value) use ($matcher) {
                    match ($matcher->numberOfInvocations()) {
                        1 => $this->assertEquals('default-src *', $value),
                        2 => $this->assertEquals(
                            'default-src *; script-src https://test.com https://com.com',
                            $value
                        ),
                        3 => $this->assertEquals(
                            'default-src *; script-src https://test.com https://com.com third.com',
                            $value
                        ),
                    };
                }
            )
        ;

        $responseHelper = $this->responseHelper;
        $responseHelper->setContentSecurityPolicy('default-src', '*');
        $responseHelper->setContentSecurityPolicy('script-src', 'https://test.com', 'https://com.com');
        $responseHelper->setContentSecurityPolicy('script-src', 'third.com');
    }
}
