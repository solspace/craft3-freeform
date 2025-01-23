<?php

namespace Solspace\Freeform\Library\Logging\Processors;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

class RedactSensitiveInfoProcessor implements ProcessorInterface
{
    private const VISIBLE_CHARS = 4;
    private const REDACT = '**********';

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

    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        if ($record instanceof LogRecord) {
            $context = $record->context;
        } else {
            $context = $record['context'];
        }

        $context = $this->traverseRedactables($context);

        if ($record instanceof LogRecord) {
            return $record->with(context: $context);
        }

        $record['context'] = $context;

        return $record;
    }

    private function traverseRedactables(mixed $data): mixed
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
                        $data[$key] = $this->redactValue($value);

                        continue 2;
                    }
                }

                if (\in_array($key, self::SENSITIVE_KEYS, false)) {
                    $data[$key] = $this->redactValue($value);
                } else {
                    $data[$key] = $this->traverseRedactables($value);
                }
            }
        }

        if ($isObject) {
            $data = (object) $data;
        }

        return $data;
    }

    private function redactValue(mixed $value): string
    {
        if (!\is_string($value)) {
            return self::REDACT;
        }

        return substr($value, 0, self::VISIBLE_CHARS).self::REDACT;
    }
}
