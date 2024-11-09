<?php

namespace Solspace\Freeform\Library\Integrations;

use GuzzleHttp\Client;

interface APIIntegrationInterface extends IntegrationInterface
{
    public const EVENT_PROCESS_VALUE = 'process-value';
    public const EVENT_BEFORE_PROCESS_MAPPING = 'before-process-mapping';
    public const EVENT_AFTER_PROCESS_MAPPING = 'after-process-mapping';

    public function checkConnection(Client $client): bool;

    public function getApiRootUrl(): string;
}
