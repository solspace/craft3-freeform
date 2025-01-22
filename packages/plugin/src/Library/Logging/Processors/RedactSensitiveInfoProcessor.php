<?php

namespace Solspace\Freeform\Library\Logging\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RedactSensitiveInfoProcessor implements ProcessorInterface
{
    private const SENSITIVE_KEYS = [
        'api_key',
        'apiKey',
        'accessToken',
        'pass',
        'clientId',
        'signature',
    ];

    private const WILDCARD_KEYS = [
        'secret',
        'password',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;

        if (isset($context['payload'])) {
            $context['payload'] = $this->redact($context['payload']);
        }

        return $record->with(context: $context);
    }

    private function redact(mixed $data): mixed
    {
        $isObject = false;
        if ($data instanceof \stdClass) {
            $data = (array) $data;
            $isObject = true;
        }

        if (\is_array($data)) {
            foreach ($data as $key => $value) {
                // check wildcard keys first
                foreach (self::WILDCARD_KEYS as $wildcard) {
                    if (str_contains(strtolower($key), strtolower($wildcard))) {
                        $data[$key] = '**REDACTED**';

                        continue 2;
                    }
                }

                if (\in_array($key, self::SENSITIVE_KEYS, false)) {
                    $data[$key] = '**REDACTED**';
                } else {
                    $data[$key] = $this->redact($value);
                }
            }
        }

        if ($isObject) {
            $data = (object) $data;
        }

        return $data;
    }
}
