<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\Transformers\ManifestTransformer;
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

    #[Flag(self::FLAG_INSTANCE_ONLY)]
    #[Input\Text(
        label: 'URL the Form Monitor should access to check the form',
        instructions: 'This is the URL that Form Monitor will use to check the form. It should be a publicly accessible URL and contain the form.',
        placeholder: 'https://example.com/contact-us',
    )]
    private string $testUrl = '';

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getTestUrl(): string
    {
        return $this->getProcessedValue($this->testUrl);
    }

    public function getApiRootUrl(): string
    {
        return 'https://api.formmonitor.com/v1';
    }

    public function checkConnection(Client $client): bool
    {
        try {
            $response = $client->get('/me');

            return 200 === $response->getStatusCode();
        } catch (\Exception) {
            return false;
        }
    }

    public function sendManifest(Client $client, Form $form, ManifestTransformer $transformer): void
    {
        $endpoint = $this->getEndpoint('forms/'.$form->getId());
        $payload = [
            'url' => $this->getTestUrl(),
            'manifest' => $transformer->transform($form),
        ];

        $client->put($endpoint, ['json' => $payload]);
    }

    protected function getProcessableFields(string $category): array
    {
        return [];
    }
}
