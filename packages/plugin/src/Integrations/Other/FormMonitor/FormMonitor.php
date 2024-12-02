<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Library\Integrations\APIIntegration;

#[Edition(Edition::PRO)]
#[Type(
    name: 'Form Monitor',
    type: Type::TYPE_OTHER,
    version: 'v1',
    readme: __DIR__.'/README.md',
    iconPath: __DIR__.'/icon.svg',
)]
class FormMonitor extends APIIntegration
{
    #[Flag(self::FLAG_ENCRYPTED)]
    #[Input\Hidden]
    private string $apiKey = '';

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function checkConnection(Client $client): bool
    {
        return true;
    }

    public function getApiRootUrl(): string
    {
        return 'https://api.formmonitor.com';
    }

    protected function getProcessableFields(string $category): array
    {
        return [];
    }
}
