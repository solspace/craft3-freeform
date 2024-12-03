<?php

namespace Solspace\Freeform\Integrations\Other\FormMonitor;

use GuzzleHttp\Client;
use Solspace\Freeform\Attributes\Integration\Type;
use Solspace\Freeform\Attributes\Property\Edition;
use Solspace\Freeform\Attributes\Property\Flag;
use Solspace\Freeform\Attributes\Property\Input;
use Solspace\Freeform\Form\Form;
use Solspace\Freeform\Integrations\Other\FormMonitor\Transformers\FormMonitorFieldTransformer;
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

    public function sync(Client $client, Form $form, FormMonitorFieldTransformer $fieldTransformer): void
    {
        $serialized = [];
        foreach ($form->getLayout()->getPages() as $page) {
            $pageData = [];

            foreach ($page->getRows() as $row) {
                $rowData = [];

                foreach ($row->getFields() as $field) {
                    $rowData[] = $fieldTransformer->transform($field);
                }

                $pageData[] = $rowData;
            }

            $serialized[] = $pageData;
        }

        $endpoint = $this->getEndpoint('forms/'.$form->getId());

        $client->put($endpoint, ['json' => $serialized]);
    }

    protected function getProcessableFields(string $category): array
    {
        return [];
    }
}
