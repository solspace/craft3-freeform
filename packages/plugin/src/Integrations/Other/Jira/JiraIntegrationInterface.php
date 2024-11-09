<?php

namespace Solspace\Freeform\Integrations\Other\Jira;

use GuzzleHttp\Client;
use Solspace\Freeform\Library\Integrations\APIIntegrationInterface;

interface JiraIntegrationInterface extends APIIntegrationInterface
{
    public function getCloudId(): string;

    public function setCloudId(string $cloudId): self;

    public function getInstanceUrl(): string;

    public function setInstanceUrl(string $instanceUrl): self;

    public function populateParameters(Client $client): void;
}
