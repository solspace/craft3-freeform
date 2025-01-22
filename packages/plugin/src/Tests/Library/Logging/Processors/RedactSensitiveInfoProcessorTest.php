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
                'payload' => [
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
            ],
        );

        $processor = new RedactSensitiveInfoProcessor();
        $output = $processor($logRecord);

        self::assertEquals(
            [
                'pass' => '**REDACTED**',
                'api_key' => '**REDACTED**',
                'clientId' => '**REDACTED**',
                'secret' => '**REDACTED**',
                'clientSecret' => '**REDACTED**',
                'password' => '**REDACTED**',
                'somePassword' => '**REDACTED**',
                'other' => 'value',
                'nested' => [
                    'pass' => '**REDACTED**',
                    'api_key' => '**REDACTED**',
                ],
            ],
            $output->context['payload'],
        );
    }

    public function testRedactStdClass()
    {
        $logRecord = new LogRecord(
            new \DateTimeImmutable(),
            'channel',
            Level::Info,
            'This is a message',
            [
                'payload' => (object) [
                    'pass' => 'password',
                    'api_key' => 'api key',
                    'clientId' => 'client',
                    'secret' => 'secret',
                    'clientSecret' => 'secret',
                    'password' => 'password',
                    'somePassword' => 'password',
                    'other' => 'value',
                    'nested' => [
                        'pass' => '**REDACTED**',
                        'api_key' => '**REDACTED**',
                    ],
                ],
            ],
        );

        $processor = new RedactSensitiveInfoProcessor();
        $output = $processor($logRecord);

        self::assertEquals(
            (object) [
                'pass' => '**REDACTED**',
                'api_key' => '**REDACTED**',
                'clientId' => '**REDACTED**',
                'secret' => '**REDACTED**',
                'clientSecret' => '**REDACTED**',
                'password' => '**REDACTED**',
                'somePassword' => '**REDACTED**',
                'other' => 'value',
                'nested' => [
                    'pass' => '**REDACTED**',
                    'api_key' => '**REDACTED**',
                ],
            ],
            $output->context['payload'],
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
                'payload' => [
                    'pass' => 'password',
                    'api_key' => 'api key',
                    'clientId' => 'client',
                    'secret' => 'secret',
                    'clientSecret' => 'secret',
                    'password' => 'password',
                    'somePassword' => 'password',
                    'other' => 'value',
                    'nested' => (object) [
                        'pass' => '**REDACTED**',
                        'api_key' => '**REDACTED**',
                    ],
                ],
            ],
        );

        $processor = new RedactSensitiveInfoProcessor();
        $output = $processor($logRecord);

        self::assertEquals(
            [
                'pass' => '**REDACTED**',
                'api_key' => '**REDACTED**',
                'clientId' => '**REDACTED**',
                'secret' => '**REDACTED**',
                'clientSecret' => '**REDACTED**',
                'password' => '**REDACTED**',
                'somePassword' => '**REDACTED**',
                'other' => 'value',
                'nested' => (object) [
                    'pass' => '**REDACTED**',
                    'api_key' => '**REDACTED**',
                ],
            ],
            $output->context['payload'],
        );
    }
}
