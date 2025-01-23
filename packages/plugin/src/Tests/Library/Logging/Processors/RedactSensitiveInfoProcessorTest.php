<?php

namespace Solspace\Freeform\Tests\Library\Logging\Processors;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use Solspace\Freeform\Library\Logging\Processors\RedactSensitiveInfoProcessor;

/**
 * @coversNothing
 */
class RedactSensitiveInfoProcessorTest extends TestCase
{
    public function testRedact(): void
    {
        $logRecord = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'This is a message',
            [
                'pass' => 'password',
                'api_key' => 'api key',
                'clientId' => 'client',
                'secret' => 'secret',
                'clientSecret' => 'secret',
                'password' => 'password',
                'somePassword' => 'password',
                'other' => 'value',
                'nested' => [
                    'pass' => 'nested pass',
                    'api_key' => 'nested api key',
                ],
            ],
        );

        $processor = new RedactSensitiveInfoProcessor();
        $output = $processor($logRecord);

        self::assertEquals(
            [
                'pass' => 'pass**********',
                'api_key' => 'api **********',
                'clientId' => 'clie**********',
                'secret' => 'secr**********',
                'clientSecret' => 'secr**********',
                'password' => 'pass**********',
                'somePassword' => 'pass**********',
                'other' => 'value',
                'nested' => [
                    'pass' => 'nest**********',
                    'api_key' => 'nest**********',
                ],
            ],
            $output->context,
        );
    }

    public function testRedactMixed()
    {
        $logRecord = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'This is a message',
            [
                'pass' => 'password',
                'api_key' => 'api key',
                'clientId' => 'client',
                'secret' => 'secret',
                'clientSecret' => 'secret',
                'password' => 'password',
                'somePassword' => 'password',
                'other' => 'value',
                'nested' => (object) [
                    'pass' => 'nested password',
                    'api_key' => 'nested api key',
                ],
            ],
        );

        $processor = new RedactSensitiveInfoProcessor();
        $output = $processor($logRecord);

        self::assertEquals(
            [
                'pass' => 'pass**********',
                'api_key' => 'api **********',
                'clientId' => 'clie**********',
                'secret' => 'secr**********',
                'clientSecret' => 'secr**********',
                'password' => 'pass**********',
                'somePassword' => 'pass**********',
                'other' => 'value',
                'nested' => (object) [
                    'pass' => 'nest**********',
                    'api_key' => 'nest**********',
                ],
            ],
            $output->context,
        );
    }
}
